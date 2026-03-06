<?php
declare(strict_types=1);

/**
 * Clase AuthService
 *
 * Logica de negocio para autenticacion (login, logout, token).
 * No toca $_SERVER, $_POST, $_GET.
 */
final class AuthService
{
    /** @var UsuarioDAO */
    private UsuarioDAO $usuarioDAO;

    /** @var TokenDAO */
    private TokenDAO $tokenDAO;

    /** @var LoginIntentoDAO */
    private LoginIntentoDAO $loginIntentoDAO;

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
        $this->tokenDAO   = new TokenDAO();
        $this->loginIntentoDAO = new LoginIntentoDAO();
    }

    /**
     * Autentica un usuario y genera un token Bearer.
     *
     * @param string $usuario  Nombre de usuario.
     * @param string $password Password en texto plano.
     * @param string $ipCliente IP del cliente para rate limit.
     * @return array{token: string, usuario: array<string, mixed>}
     * @throws RuntimeException Si las credenciales son invalidas.
     */
    public function login(string $usuario, string $password, string $ipCliente = ''): array
    {
        $this->aplicarPoliticasSeguridad();

        $usuarioRateLimit = $this->normalizarUsuarioRateLimit($usuario);
        $ipRateLimit = $this->normalizarIpRateLimit($ipCliente);

        $this->loginIntentoDAO->purgarExpirados();
        $estadoBloqueo = $this->loginIntentoDAO->getBlockStatus($usuarioRateLimit, $ipRateLimit);

        if ($estadoBloqueo['bloqueado']) {
            $minutos = max(1, (int) ceil($estadoBloqueo['segundos_restantes'] / 60));
            throw new RuntimeException('Demasiados intentos fallidos. Intente nuevamente en ' . $minutos . ' minuto(s).');
        }

        $user = $this->usuarioDAO->findByUsuario($usuario);

        if ($user === null) {
            $this->loginIntentoDAO->registrarFallo($usuarioRateLimit, $ipRateLimit);
            throw new RuntimeException('Credenciales invalidas.');
        }

        if ($this->usuarioDAO->isPasswordExpired($user)) {
            $this->usuarioDAO->deactivate($user->id);
            $this->tokenDAO->deleteByUsuarioId($user->id);
            throw new RuntimeException('La contrasena expiro. El usuario fue desactivado, solicite reactivacion al administrador.');
        }

        if (!$user->activo) {
            throw new RuntimeException('La cuenta de usuario esta desactivada.');
        }

        if (!password_verify($password, $user->passwordHash)) {
            $this->loginIntentoDAO->registrarFallo($usuarioRateLimit, $ipRateLimit);
            throw new RuntimeException('Credenciales invalidas.');
        }

        // Login exitoso: limpiar contador de intentos.
        $this->loginIntentoDAO->limpiarIntentos($usuarioRateLimit, $ipRateLimit);

        // Generar token
        $tokenPlano = bin2hex(random_bytes(32));
        $tokenHash  = hash('sha256', $tokenPlano);

        // Eliminar tokens anteriores del usuario (un solo token activo)
        $this->tokenDAO->deleteByUsuarioId($user->id);

        $expiraEn = (new DateTimeImmutable('now'))
            ->modify('+' . SESSION_MAX_DURATION_HOURS . ' hours')
            ->format('Y-m-d H:i:s');

        // Insertar nuevo token
        $this->tokenDAO->insert($user->id, $tokenHash, $expiraEn);

        return [
            'token'   => $tokenPlano,
            'usuario' => [
                'id'              => $user->id,
                'nombre_completo' => $user->nombreCompleto,
                'usuario'         => $user->usuario,
                'rol'             => $user->rolNombre,
                'activo'          => $user->activo,
                'password_actualizada_en' => $user->passwordActualizadaEn,
                'password_expira_en' => $this->resolverExpiracionPassword($user)
            ],
            'session' => [
                'expira_en' => $expiraEn,
                'idle_timeout_minutos' => SESSION_IDLE_TIMEOUT_MINUTES
            ]
        ];
    }

    /**
     * Cierra la sesion revocando el token actual.
     *
     * @param string $tokenPlano Token Bearer enviado por el cliente.
     * @return void
     */
    public function logout(string $tokenPlano): void
    {
        $tokenHash = hash('sha256', $tokenPlano);
        $this->tokenDAO->deleteByHash($tokenHash);
    }

    /**
     * Obtiene los datos del usuario autenticado.
     *
     * @param int $usuarioId ID del usuario autenticado.
     * @return array<string, mixed> Datos del usuario.
     * @throws RuntimeException Si no se encuentra el usuario.
     */
    public function me(int $usuarioId): array
    {
        $this->aplicarPoliticasSeguridad();

        $user = $this->usuarioDAO->findById($usuarioId);

        if ($user === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        return UsuarioMapper::toArray($user);
    }

    /**
     * Ejecuta politicas de seguridad de forma centralizada.
     *
     * @return void
     */
    private function aplicarPoliticasSeguridad(): void
    {
        $this->usuarioDAO->deactivateExpiredPasswords();
        $this->tokenDAO->deleteByInvalidUsers();
        $this->tokenDAO->deleteExpiredSessions();
    }

    /**
     * Obtiene la fecha de expiracion del password de forma resiliente.
     *
     * @param UsuarioDTO $user
     * @return string
     */
    private function resolverExpiracionPassword(UsuarioDTO $user): string
    {
        if ($user->passwordExpiraEn !== '') {
            return $user->passwordExpiraEn;
        }

        $base = $user->passwordActualizadaEn !== ''
            ? $user->passwordActualizadaEn
            : $user->creadoEn;

        if ($base === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable($base))
                ->modify('+' . PASSWORD_EXPIRY_DAYS . ' days')
                ->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param string $usuario
     * @return string
     */
    private function normalizarUsuarioRateLimit(string $usuario): string
    {
        $normalizado = strtolower(trim($usuario));
        return $normalizado !== '' ? $normalizado : 'desconocido';
    }

    /**
     * @param string $ip
     * @return string
     */
    private function normalizarIpRateLimit(string $ip): string
    {
        $valor = trim($ip);

        if ($valor !== '' && filter_var($valor, FILTER_VALIDATE_IP)) {
            return $valor;
        }

        return '0.0.0.0';
    }
}
