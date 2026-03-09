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
    private const RATE_LIMIT_CLIENT_SCOPE_USER = '__client_scope__';

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
     * @param string $dispositivoCliente Identificador de dispositivo enviado por frontend.
     * @return array{token: string, usuario: array<string, mixed>}
     * @throws RuntimeException Si las credenciales son invalidas.
     */
    public function login(
        string $usuario,
        string $password,
        string $ipCliente = '',
        string $dispositivoCliente = ''
    ): array
    {
        $this->aplicarPoliticasSeguridad();

        $usuarioRateLimit = $this->normalizarUsuarioRateLimit($usuario);
        $ipRateLimit = $this->normalizarIpRateLimit($ipCliente);
        $dispositivoRateLimit = $this->normalizarDispositivoRateLimit($dispositivoCliente);
        $scopeRateLimit = $this->resolverScopeRateLimit($ipRateLimit, $dispositivoRateLimit);

        $this->loginIntentoDAO->purgarExpirados();
        $this->validarBloqueoRateLimit($usuarioRateLimit, $scopeRateLimit);

        $user = $this->usuarioDAO->findByUsuario($usuario);

        if ($user === null) {
            $this->registrarFalloRateLimit($usuarioRateLimit, $scopeRateLimit);
            throw new RuntimeException('Credenciales invalidas.');
        }

        if ($this->usuarioDAO->isPasswordExpired($user)) {
            $this->registrarFalloRateLimit($usuarioRateLimit, $scopeRateLimit);
            $this->usuarioDAO->deactivate($user->id);
            $this->tokenDAO->deleteByUsuarioId($user->id);
            throw new RuntimeException('La contrasena expiro. El usuario fue desactivado, solicite reactivacion al administrador.');
        }

        if (!$user->activo) {
            $this->registrarFalloRateLimit($usuarioRateLimit, $scopeRateLimit);
            throw new RuntimeException('La cuenta de usuario esta desactivada.');
        }

        if (!password_verify($password, $user->passwordHash)) {
            $this->registrarFalloRateLimit($usuarioRateLimit, $scopeRateLimit);
            throw new RuntimeException('Credenciales invalidas.');
        }

        // Login exitoso: limpiar contador de intentos.
        $this->limpiarRateLimit($usuarioRateLimit, $scopeRateLimit);

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
            if ($valor === '::1' || $valor === '0:0:0:0:0:0:0:1') {
                return '127.0.0.1';
            }

            if (str_starts_with(strtolower($valor), '::ffff:')) {
                $posibleIpv4 = substr($valor, 7);
                if ($posibleIpv4 !== '' && filter_var($posibleIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $posibleIpv4;
                }
            }

            return $valor;
        }

        return '0.0.0.0';
    }

    /**
     * @param string $dispositivo
     * @return string
     */
    private function normalizarDispositivoRateLimit(string $dispositivo): string
    {
        $valor = trim($dispositivo);
        if ($valor === '') {
            return '';
        }

        if (strlen($valor) > 120) {
            $valor = substr($valor, 0, 120);
        }

        if (!preg_match('/^[A-Za-z0-9._:-]+$/', $valor)) {
            return '';
        }

        return strtolower($valor);
    }

    /**
     * Resuelve la clave de scope para rate limit.
     * Si hay ID de dispositivo se usa hash de IP+dispositivo para no bloquear
     * a todos los clientes detras de la misma red.
     *
     * @param string $ipRateLimit
     * @param string $dispositivoRateLimit
     * @return string
     */
    private function resolverScopeRateLimit(string $ipRateLimit, string $dispositivoRateLimit): string
    {
        if ($dispositivoRateLimit === '') {
            return $ipRateLimit;
        }

        return 'd:' . hash('sha1', $ipRateLimit . '|' . $dispositivoRateLimit);
    }

    /**
     * Valida bloqueo por usuario+scope y tambien por scope global.
     *
     * @param string $usuarioRateLimit
     * @param string $scopeRateLimit
     * @return void
     */
    private function validarBloqueoRateLimit(string $usuarioRateLimit, string $scopeRateLimit): void
    {
        $estadoUsuarioScope = $this->loginIntentoDAO->getBlockStatus($usuarioRateLimit, $scopeRateLimit);
        $estadoScopeGlobal = $this->loginIntentoDAO->getBlockStatus(self::RATE_LIMIT_CLIENT_SCOPE_USER, $scopeRateLimit);

        if (!$estadoUsuarioScope['bloqueado'] && !$estadoScopeGlobal['bloqueado']) {
            return;
        }

        $segundosRestantes = max(
            (int) ($estadoUsuarioScope['segundos_restantes'] ?? 0),
            (int) ($estadoScopeGlobal['segundos_restantes'] ?? 0)
        );
        $minutos = max(1, (int) ceil($segundosRestantes / 60));

        throw new RuntimeException('Demasiados intentos fallidos. Intente nuevamente en ' . $minutos . ' minuto(s).');
    }

    /**
     * Registra fallo de login en scope usuario+cliente y en scope global de cliente.
     *
     * @param string $usuarioRateLimit
     * @param string $scopeRateLimit
     * @return void
     */
    private function registrarFalloRateLimit(string $usuarioRateLimit, string $scopeRateLimit): void
    {
        $this->loginIntentoDAO->registrarFallo($usuarioRateLimit, $scopeRateLimit);
        $this->loginIntentoDAO->registrarFallo(self::RATE_LIMIT_CLIENT_SCOPE_USER, $scopeRateLimit);
    }

    /**
     * Limpia contadores de login al autenticar correctamente.
     *
     * @param string $usuarioRateLimit
     * @param string $scopeRateLimit
     * @return void
     */
    private function limpiarRateLimit(string $usuarioRateLimit, string $scopeRateLimit): void
    {
        $this->loginIntentoDAO->limpiarIntentos($usuarioRateLimit, $scopeRateLimit);
        $this->loginIntentoDAO->limpiarIntentos(self::RATE_LIMIT_CLIENT_SCOPE_USER, $scopeRateLimit);
    }
}
