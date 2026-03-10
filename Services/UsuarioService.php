<?php
declare(strict_types=1);

/**
 * Clase UsuarioService
 *
 * Logica de negocio para la entidad Usuario.
 * No toca $_SERVER, $_POST, $_GET.
 */
final class UsuarioService
{
    /** @var UsuarioDAO */
    private UsuarioDAO $usuarioDAO;

    /** @var TokenDAO */
    private TokenDAO $tokenDAO;

    /** @var array<string, int> */
    private const CUPOS_DEFAULT = [
        'ADMIN' => 2,
        'SECRETARIO' => 2,
        'MINISTERIO_PERSONAL' => 3
    ];

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
        $this->tokenDAO = new TokenDAO();
    }

    /**
     * Lista usuarios de la organizacion autenticada.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $usuarios = $this->usuarioDAO->findAllByOrganizacion($organizacionId);
        $resultado = [];

        foreach ($usuarios as $usuario) {
            $resultado[] = UsuarioMapper::toArray($usuario);
        }

        return $resultado;
    }

    /**
     * Obtiene un usuario por ID dentro de la organizacion autenticada.
     *
     * @param int $id ID del usuario.
     * @return array<string, mixed>
     * @throws RuntimeException Si no se encuentra.
     */
    public function obtenerPorId(int $id): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $usuario = $this->usuarioDAO->findByIdInOrganizacion($id, $organizacionId);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        return UsuarioMapper::toArray($usuario);
    }

    /**
     * Crea un nuevo usuario dentro de la organizacion autenticada.
     *
     * @param array<string, mixed> $data Datos validados.
     * @return array<string, mixed> Datos del usuario creado.
     * @throws RuntimeException Si el usuario ya existe o no hay cupo disponible.
     */
    public function crear(array $data): array
    {
        $organizacionId = AuthContext::getOrganizacionId();

        if ($this->usuarioDAO->existsByUsuario($data['usuario'])) {
            throw new RuntimeException('El nombre de usuario ya esta registrado.');
        }

        $rolNombre = $this->resolverRolNombrePorId((int) $data['rol_id']);
        $this->assertCupoDisponible($organizacionId, $rolNombre, null);

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        if (!is_string($passwordHash)) {
            throw new RuntimeException('No fue posible generar la contrasena segura del usuario.');
        }

        $id = $this->usuarioDAO->insert(
            (string) $data['nombre_completo'],
            (string) $data['usuario'],
            $passwordHash,
            (int) $data['rol_id'],
            $organizacionId
        );

        return $this->obtenerPorId($id);
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param int                  $id   ID del usuario.
     * @param array<string, mixed> $data Datos validados.
     * @return array<string, mixed> Datos del usuario actualizado.
     * @throws RuntimeException Si no se encuentra o hay duplicado/cupo.
     */
    public function actualizar(int $id, array $data): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $usuario = $this->usuarioDAO->findByIdInOrganizacion($id, $organizacionId);
        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        if ($this->usuarioDAO->existsByUsuario($data['usuario'], $id)) {
            throw new RuntimeException('El nombre de usuario ya esta registrado por otro usuario.');
        }

        $quiereReactivar = ($usuario->activo === false) && ($data['activo'] === true);
        if ($quiereReactivar && empty($data['password'])) {
            throw new RuntimeException('Para reactivar un usuario inactivo se requiere cambiar su contrasena.');
        }

        $rolDestino = $this->resolverRolNombrePorId((int) $data['rol_id']);
        $rolActual = strtoupper((string) $usuario->rolNombre);
        $debeVerificarCupo = ((bool) $data['activo']) && (!$usuario->activo || $rolDestino !== $rolActual);

        if ($debeVerificarCupo) {
            $this->assertCupoDisponible($organizacionId, $rolDestino, $id);
        }

        $this->usuarioDAO->update(
            $id,
            (string) $data['nombre_completo'],
            (string) $data['usuario'],
            (int) $data['rol_id'],
            (bool) $data['activo']
        );

        // Actualizar password si se envio.
        if (!empty($data['password'])) {
            $passwordHash = password_hash((string) $data['password'], PASSWORD_BCRYPT);
            if (!is_string($passwordHash)) {
                throw new RuntimeException('No fue posible actualizar la contrasena del usuario.');
            }
            $this->usuarioDAO->updatePassword($id, $passwordHash);
            $this->tokenDAO->deleteByUsuarioId($id);
        }

        // Si el usuario queda inactivo, revocar sus sesiones.
        if ((bool) $data['activo'] === false) {
            $this->tokenDAO->deleteByUsuarioId($id);
        }

        return $this->obtenerPorId($id);
    }

    /**
     * Desactiva un usuario (soft delete) dentro de la organizacion autenticada.
     *
     * @param int $id ID del usuario.
     * @return void
     * @throws RuntimeException Si no se encuentra.
     */
    public function eliminar(int $id): void
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $usuario = $this->usuarioDAO->findByIdInOrganizacion($id, $organizacionId);
        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $this->usuarioDAO->deactivate($id);
        $this->tokenDAO->deleteByUsuarioId($id);
    }

    /**
     * Obtiene configuracion de cupos y consumo actual por rol de la organizacion autenticada.
     *
     * @return array<string, mixed>
     */
    public function obtenerCuposRoles(): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $config = $this->usuarioDAO->getRolesCuposByOrganizacion($organizacionId);
        $consumo = $this->usuarioDAO->countActivosPorRolMapEnOrganizacion($organizacionId);

        $rolesMap = [];

        foreach ($config as $row) {
            $rol = strtoupper((string) ($row['rol_nombre'] ?? ''));
            if ($rol === '') {
                continue;
            }

            $rolesMap[$rol] = [
                'rol_nombre' => $rol,
                'cupo_maximo' => (int) ($row['cupo_maximo'] ?? 0),
                'activo' => (int) ($row['activo'] ?? 0) === 1,
                'origen' => 'configuracion'
            ];
        }

        foreach (self::CUPOS_DEFAULT as $rol => $cupoDefault) {
            if (!isset($rolesMap[$rol])) {
                $rolesMap[$rol] = [
                    'rol_nombre' => $rol,
                    'cupo_maximo' => $cupoDefault,
                    'activo' => true,
                    'origen' => 'default'
                ];
            }
        }

        foreach ($consumo as $rol => $usados) {
            $rolNorm = strtoupper((string) $rol);
            if (!isset($rolesMap[$rolNorm])) {
                $rolesMap[$rolNorm] = [
                    'rol_nombre' => $rolNorm,
                    'cupo_maximo' => max(0, (int) $usados),
                    'activo' => false,
                    'origen' => 'sin_configuracion'
                ];
            }
        }

        ksort($rolesMap);
        $roles = [];
        $rolesActivos = 0;
        $cuposTotales = 0;
        $usuariosContabilizados = 0;
        $rolesExcedidos = 0;

        foreach ($rolesMap as $rol => $item) {
            $consumoRol = (int) ($consumo[$rol] ?? 0);
            $cupo = (int) $item['cupo_maximo'];
            $activo = (bool) $item['activo'];
            $disponible = $activo ? max(0, $cupo - $consumoRol) : 0;
            $excedido = $activo && ($consumoRol > $cupo);

            if ($activo) {
                $rolesActivos++;
                $cuposTotales += $cupo;
                $usuariosContabilizados += $consumoRol;
            }
            if ($excedido) {
                $rolesExcedidos++;
            }

            $roles[] = [
                'rol_nombre' => $rol,
                'cupo_maximo' => $cupo,
                'activo' => $activo,
                'consumo_actual' => $consumoRol,
                'disponibles' => $disponible,
                'excedido' => $excedido,
                'origen' => (string) $item['origen']
            ];
        }

        return [
            'roles' => $roles,
            'resumen' => [
                'total_roles' => count($roles),
                'roles_activos' => $rolesActivos,
                'cupos_totales' => $cuposTotales,
                'usuarios_contabilizados' => $usuariosContabilizados,
                'roles_excedidos' => $rolesExcedidos
            ]
        ];
    }

    /**
     * Reemplaza configuracion de cupos por rol para la organizacion autenticada.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizarCuposRoles(array $data): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        /** @var array<int, array<string, mixed>> $cupos */
        $cupos = $data['cupos'];

        $consumo = $this->usuarioDAO->countActivosPorRolMapEnOrganizacion($organizacionId);

        foreach ($cupos as $item) {
            $rol = strtoupper((string) $item['rol_nombre']);
            $cupo = (int) $item['cupo_maximo'];
            $activo = (bool) $item['activo'];
            $consumoActual = (int) ($consumo[$rol] ?? 0);

            if ($rol === 'SUPERADMIN') {
                throw new InvalidArgumentException('No se permite configurar cupos para SUPERADMIN en instancia.');
            }

            if ($activo && $cupo < $consumoActual) {
                throw new RuntimeException(
                    'El cupo de "' . $rol . '" no puede ser menor que el consumo actual (' . $consumoActual . ').'
                );
            }
        }

        $this->usuarioDAO->replaceRolesCuposByOrganizacion($organizacionId, $cupos);

        return $this->obtenerCuposRoles();
    }

    /**
     * Resuelve nombre de rol por ID y aplica restricciones de seguridad.
     *
     * @param int $rolId
     * @return string
     */
    private function resolverRolNombrePorId(int $rolId): string
    {
        $rolNombre = $this->usuarioDAO->findRolNombreById($rolId);
        if ($rolNombre === null || $rolNombre === '') {
            throw new InvalidArgumentException('El rol indicado no existe.');
        }

        $rol = strtoupper($rolNombre);
        if ($rol === 'SUPERADMIN') {
            throw new InvalidArgumentException('No se permite crear o editar usuarios SUPERADMIN desde instancia.');
        }

        return $rol;
    }

    /**
     * Verifica cupo disponible para un rol dentro de una organizacion.
     *
     * @param int      $organizacionId
     * @param string   $rolNombre
     * @param int|null $excludeUsuarioId
     * @return void
     */
    private function assertCupoDisponible(int $organizacionId, string $rolNombre, ?int $excludeUsuarioId): void
    {
        $config = $this->usuarioDAO->getCupoByRolEnOrganizacion($organizacionId, $rolNombre);

        $activo = true;
        $cupo = null;

        if ($config !== null) {
            $activo = (int) ($config['activo'] ?? 0) === 1;
            $cupo = (int) ($config['cupo_maximo'] ?? 0);
        } elseif (isset(self::CUPOS_DEFAULT[$rolNombre])) {
            $cupo = (int) self::CUPOS_DEFAULT[$rolNombre];
            $activo = true;
        }

        // Si no hay configuracion ni default para el rol, no se aplica enforcement.
        if ($cupo === null) {
            return;
        }

        if (!$activo) {
            throw new RuntimeException('El rol "' . $rolNombre . '" esta deshabilitado por politica de cupos.');
        }

        $consumo = $this->usuarioDAO->countActivosPorRolEnOrganizacion($organizacionId, $rolNombre, $excludeUsuarioId);
        if ($consumo >= $cupo) {
            throw new RuntimeException(
                'Se alcanzo el cupo maximo para rol "' . $rolNombre . '" (' . $consumo . '/' . $cupo . ').'
            );
        }
    }
}
