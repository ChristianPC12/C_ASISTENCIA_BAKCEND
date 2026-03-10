<?php
declare(strict_types=1);

/**
 * Clase OrganizacionService
 *
 * Logica de negocio para API superadmin de organizaciones.
 */
final class OrganizacionService
{
    private const ADMIN_TEMPORAL_DIAS = 5;

    /** @var OrganizacionDAO */
    private OrganizacionDAO $organizacionDAO;

    /** @var UsuarioDAO */
    private UsuarioDAO $usuarioDAO;

    /** @var CorreoService */
    private CorreoService $correoService;

    public function __construct()
    {
        $this->organizacionDAO = new OrganizacionDAO();
        $this->usuarioDAO = new UsuarioDAO();
        $this->correoService = new CorreoService();
    }

    /**
     * Lista organizaciones con paginacion.
     *
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function listar(array $filtros): array
    {
        $page = (int) $filtros['page'];
        $limit = (int) $filtros['limit'];

        $result = $this->organizacionDAO->list($filtros, $page, $limit);
        $items = [];

        foreach ($result['items'] as $item) {
            $items[] = OrganizacionMapper::toArray($item);
        }

        $total = (int) $result['total'];
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 0;

        return [
            'items' => $items,
            'paginacion' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ],
            'filtros_aplicados' => [
                'campo' => $filtros['campo'] ?? null,
                'tipo' => $filtros['tipo'] ?? null,
                'estado' => $filtros['estado'] ?? 'TODAS',
                'q' => $filtros['q'] ?? null
            ]
        ];
    }

    /**
     * Crea una nueva organizacion.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crear(array $data): array
    {
        $campo = $this->organizacionDAO->findCampoByCodigo($data['campo']);
        if ($campo === null || (int) ($campo['activo'] ?? 0) !== 1) {
            throw new InvalidArgumentException('El campo indicado no existe o esta inactivo.');
        }

        $campoId = (int) $campo['id'];
        $tipo = (string) $data['tipo_organizacion'];
        $nombre = (string) $data['nombre_organizacion'];
        $correo = $data['correo_contacto'] ?? null;

        if ($this->organizacionDAO->existsByNombreEnCampoTipo($campoId, $tipo, $nombre)) {
            throw new RuntimeException('Ya existe una organizacion con ese nombre para el campo y tipo indicados.');
        }

        $codigoInstancia = $this->generarCodigoInstancia((string) $campo['codigo'], $tipo, $nombre);

        $pdo = $this->organizacionDAO->getPdo();
        $organizacionId = 0;

        try {
            $pdo->beginTransaction();

            $organizacionId = $this->organizacionDAO->insert(
                $campoId,
                $codigoInstancia,
                $tipo,
                $nombre,
                is_string($correo) ? $correo : null
            );

            $this->organizacionDAO->ensureConfigEstado($organizacionId);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($this->esConflictoDuplicado($e)) {
                throw new RuntimeException('No fue posible crear la organizacion por conflicto de duplicados.');
            }

            throw $e;
        }

        $organizacion = $this->organizacionDAO->findById($organizacionId);
        if ($organizacion === null) {
            throw new RuntimeException('No fue posible recuperar la organizacion creada.');
        }

        error_log('[SUPERADMIN] Organizacion creada: id=' . $organizacionId . ', codigo=' . $codigoInstancia);

        return [
            'organizacion_id' => $organizacion->id,
            'codigo_instancia' => $organizacion->codigoInstancia,
            'organizacion' => OrganizacionMapper::toArray($organizacion)
        ];
    }

    /**
     * Actualiza datos editables de una organizacion.
     *
     * @param int                  $organizacionId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizar(int $organizacionId, array $data): array
    {
        $existente = $this->organizacionDAO->findById($organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Organizacion no encontrada.');
        }

        $nuevoTipo = (string) $data['tipo_organizacion'];
        $nuevoNombre = (string) $data['nombre_organizacion'];
        $nuevoCorreo = $data['correo_contacto'] ?? null;
        $nuevaActiva = array_key_exists('activa', $data) && is_bool($data['activa'])
            ? $data['activa']
            : $existente->activa;

        if ($this->organizacionDAO->existsByNombreEnCampoTipo(
            $existente->campoId,
            $nuevoTipo,
            $nuevoNombre,
            $organizacionId
        )) {
            throw new RuntimeException('Ya existe una organizacion con ese nombre para el campo y tipo indicados.');
        }

        try {
            $this->organizacionDAO->update(
                $organizacionId,
                $nuevoTipo,
                $nuevoNombre,
                is_string($nuevoCorreo) ? $nuevoCorreo : null,
                $nuevaActiva
            );
        } catch (\Throwable $e) {
            if ($this->esConflictoDuplicado($e)) {
                throw new RuntimeException('No fue posible actualizar la organizacion por conflicto de duplicados.');
            }

            throw $e;
        }

        $actualizada = $this->organizacionDAO->findById($organizacionId);
        if ($actualizada === null) {
            throw new RuntimeException('No fue posible recuperar la organizacion actualizada.');
        }

        error_log('[SUPERADMIN] Organizacion actualizada: id=' . $organizacionId);

        return [
            'organizacion_id' => $actualizada->id,
            'codigo_instancia' => $actualizada->codigoInstancia,
            'organizacion' => OrganizacionMapper::toArray($actualizada)
        ];
    }

    /**
     * Crea un ADMIN temporal para la organizacion indicada.
     *
     * @param int                  $organizacionId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearAdminTemporal(int $organizacionId, array $data): array
    {
        $organizacion = $this->organizacionDAO->findById($organizacionId);
        if ($organizacion === null) {
            throw new OutOfBoundsException('Organizacion no encontrada.');
        }

        if ($organizacion->activa === false) {
            throw new RuntimeException('No se puede crear ADMIN temporal en una organizacion inactiva.');
        }

        $usuario = (string) $data['usuario'];
        if ($this->usuarioDAO->existsByUsuario($usuario)) {
            throw new RuntimeException('El nombre de usuario ya esta registrado.');
        }

        if ($this->usuarioDAO->existsAdminActivoByOrganizacion($organizacionId)) {
            throw new RuntimeException('La organizacion ya tiene un ADMIN activo.');
        }

        $rolAdminId = $this->usuarioDAO->findRolIdByNombre('ADMIN');
        if ($rolAdminId === null || $rolAdminId <= 0) {
            throw new RuntimeException('No existe rol ADMIN configurado en el sistema.');
        }

        $correoDestino = $this->resolverCorreoDestino($organizacion, $data);
        $enviarCorreo = (bool) ($data['enviar_correo'] ?? false);
        if ($enviarCorreo && $correoDestino === null) {
            throw new InvalidArgumentException(
                'No se puede enviar correo porque no hay correo destino ni correo de contacto en la organizacion.'
            );
        }

        $passwordTemporal = $this->generarPasswordTemporal();
        $passwordHash = password_hash($passwordTemporal, PASSWORD_BCRYPT);
        if ($passwordHash === false) {
            throw new RuntimeException('No se pudo generar hash de password temporal.');
        }

        $expiraEn = (new DateTimeImmutable('now'))
            ->modify('+' . self::ADMIN_TEMPORAL_DIAS . ' days')
            ->format('Y-m-d H:i:s');

        try {
            $usuarioId = $this->usuarioDAO->insertAdminTemporal(
                (string) $data['nombre_completo'],
                $usuario,
                $passwordHash,
                $rolAdminId,
                $organizacionId,
                $expiraEn
            );
        } catch (\Throwable $e) {
            if ($this->esConflictoDuplicado($e)) {
                throw new RuntimeException('No fue posible crear el ADMIN temporal por conflicto de duplicados.');
            }

            throw $e;
        }

        $correoEstado = [
            'solicitado' => $enviarCorreo,
            'destino' => $correoDestino,
            'enviado' => false,
            'proveedor' => null,
            'detalle' => $enviarCorreo ? 'Envio pendiente.' : 'Envio no solicitado.'
        ];

        if ($enviarCorreo && $correoDestino !== null) {
            try {
                $correoEstado = $this->correoService->enviarCredencialesAdminTemporal(
                    $correoDestino,
                    (string) $data['nombre_completo'],
                    $usuario,
                    $passwordTemporal,
                    $organizacion->codigoInstancia,
                    $expiraEn
                );
                $correoEstado['solicitado'] = true;
                $correoEstado['destino'] = $correoDestino;
            } catch (\Throwable $e) {
                $correoEstado = [
                    'solicitado' => true,
                    'destino' => $correoDestino,
                    'enviado' => false,
                    'proveedor' => 'brevo',
                    'detalle' => 'No se pudo enviar correo: error inesperado.'
                ];
            }
        }

        error_log(
            '[SUPERADMIN] ADMIN temporal creado: usuario_id=' . $usuarioId
            . ', organizacion_id=' . $organizacionId
            . ', envio_correo=' . (($correoEstado['enviado'] ?? false) ? '1' : '0')
        );

        return [
            'organizacion_id' => $organizacion->id,
            'codigo_instancia' => $organizacion->codigoInstancia,
            'admin_temporal' => [
                'usuario_id' => $usuarioId,
                'nombre_completo' => (string) $data['nombre_completo'],
                'usuario' => $usuario,
                'rol' => 'ADMIN',
                'expira_en' => $expiraEn,
                'password_temporal' => $passwordTemporal
            ],
            'correo' => $correoEstado
        ];
    }

    /**
     * Genera codigo_instancia unico.
     *
     * Formato:
     * - base: [CAMPO][TIPO][NOMBRE] (max 8 chars)
     * - sufijo numerico incremental de 3 digitos.
     *
     * @param string $campoCodigo
     * @param string $tipoOrganizacion
     * @param string $nombreOrganizacion
     * @return string
     */
    private function generarCodigoInstancia(
        string $campoCodigo,
        string $tipoOrganizacion,
        string $nombreOrganizacion
    ): string {
        $campo = strtoupper(trim($campoCodigo));
        $campo = preg_replace('/[^A-Z0-9]/', '', $campo) ?? '';
        $campo = substr($campo, 0, 3);
        if ($campo === '') {
            $campo = 'CMP';
        }

        $tipo = strtoupper(trim($tipoOrganizacion)) === 'IGLESIA' ? 'I' : 'G';
        $nombre = $this->normalizarTextoCodigo($nombreOrganizacion);
        $nombre = $nombre !== '' ? $nombre : 'ORG';
        $nombre = substr($nombre, 0, 4);

        $base = substr($campo . $tipo . $nombre, 0, 8);

        for ($i = 1; $i <= 999; $i++) {
            $codigo = $base . str_pad((string) $i, 3, '0', STR_PAD_LEFT);

            if (!$this->organizacionDAO->existsByCodigoInstancia($codigo)) {
                return $codigo;
            }
        }

        throw new RuntimeException('No hay codigos de instancia disponibles para esta combinacion.');
    }

    /**
     * Normaliza texto para construir codigo_instancia (ASCII alfanumerico).
     *
     * @param string $value
     * @return string
     */
    private function normalizarTextoCodigo(string $value): string
    {
        $upper = strtoupper($value);
        $ascii = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $upper)
            : $upper;

        if (!is_string($ascii) || $ascii === '') {
            $ascii = $upper;
        }

        return preg_replace('/[^A-Z0-9]/', '', $ascii) ?? '';
    }

    /**
     * Detecta conflicto de duplicado de MySQL/MariaDB.
     *
     * @param \Throwable $e
     * @return bool
     */
    private function esConflictoDuplicado(\Throwable $e): bool
    {
        if (!$e instanceof PDOException) {
            return false;
        }

        $sqlState = $e->errorInfo[0] ?? '';
        $driverCode = $e->errorInfo[1] ?? 0;

        return $sqlState === '23000' || (int) $driverCode === 1062;
    }

    /**
     * Genera password temporal fuerte.
     *
     * @return string
     */
    private function generarPasswordTemporal(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $special = '@#$%_-';
        $all = $upper . $lower . $digits . $special;

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $special[random_int(0, strlen($special) - 1)]
        ];

        for ($i = 0; $i < 12; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($chars);
        return implode('', $chars);
    }

    /**
     * Resuelve correo destino para envio opcional.
     *
     * @param OrganizacionDTO      $organizacion
     * @param array<string, mixed> $data
     * @return string|null
     */
    private function resolverCorreoDestino(OrganizacionDTO $organizacion, array $data): ?string
    {
        $correoDestino = $data['correo_destino'] ?? null;
        if (is_string($correoDestino) && $correoDestino !== '') {
            return $correoDestino;
        }

        if (is_string($organizacion->correoContacto) && $organizacion->correoContacto !== '') {
            return $organizacion->correoContacto;
        }

        return null;
    }
}
