<?php
declare(strict_types=1);
/**
 * Clase LoginIntentoDAO
 *
 * Gestiona intentos fallidos de login por usuario + IP.
 */
final class LoginIntentoDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * Verifica si el usuario+IP esta bloqueado actualmente.
     *
     * @param string $usuario
     * @param string $ip
     * @return array{bloqueado: bool, segundos_restantes: int}
     */
    public function getBlockStatus(string $usuario, string $ip): array
    {
        $sql = "SELECT
                    bloqueado_hasta,
                    CASE
                        WHEN bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW() THEN 1
                        ELSE 0
                    END AS bloqueado_activo,
                    CASE
                        WHEN bloqueado_hasta IS NOT NULL AND bloqueado_hasta <= NOW() THEN 1
                        ELSE 0
                    END AS bloqueo_vencido,
                    CASE
                        WHEN bloqueado_hasta IS NULL
                             AND DATE_ADD(primer_intento_en, INTERVAL " . (int) LOGIN_ATTEMPT_WINDOW_MINUTES . " MINUTE) <= NOW() THEN 1
                        ELSE 0
                    END AS ventana_vencida,
                    GREATEST(TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta), 0) AS segundos_restantes
                FROM login_intentos
                WHERE usuario = :usuario AND ip = :ip
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':ip'      => $ip
        ]);

        $row = $stmt->fetch();
        if ($row === false) {
            return [
                'bloqueado' => false,
                'segundos_restantes' => 0
            ];
        }

        $bloqueadoActivo = (int) ($row['bloqueado_activo'] ?? 0) === 1;
        if ($bloqueadoActivo) {
            return [
                'bloqueado' => true,
                'segundos_restantes' => max(0, (int) ($row['segundos_restantes'] ?? 0))
            ];
        }

        $bloqueoVencido = (int) ($row['bloqueo_vencido'] ?? 0) === 1;
        $ventanaVencida = (int) ($row['ventana_vencida'] ?? 0) === 1;
        if ($bloqueoVencido || $ventanaVencida) {
            // Limpiar estado para permitir nuevos intentos.
            $this->limpiarIntentos($usuario, $ip);
        }

        return [
            'bloqueado' => false,
            'segundos_restantes' => 0
        ];
    }

    /**
     * Registra un intento fallido.
     * Si supera el umbral, bloquea por el tiempo configurado.
     *
     * @param string $usuario
     * @param string $ip
     * @return void
     */
    public function registrarFallo(string $usuario, string $ip): void
    {
        $sqlSelect = "SELECT intentos, primer_intento_en, bloqueado_hasta
                      FROM login_intentos
                      WHERE usuario = :usuario AND ip = :ip
                      LIMIT 1
                      FOR UPDATE";

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare($sqlSelect);
            $stmt->execute([
                ':usuario' => $usuario,
                ':ip'      => $ip
            ]);
            $row = $stmt->fetch();

            if ($row === false) {
                $insert = "INSERT INTO login_intentos
                           (usuario, ip, intentos, primer_intento_en, ultimo_intento_en, bloqueado_hasta)
                           VALUES (:usuario, :ip, 1, NOW(), NOW(), NULL)";

                $stmtInsert = $this->pdo->prepare($insert);
                $stmtInsert->execute([
                    ':usuario' => $usuario,
                    ':ip'      => $ip
                ]);

                $this->pdo->commit();
                return;
            }

            $intentosActuales = (int) ($row['intentos'] ?? 0);
            $bloqueadoHastaRaw = (string) ($row['bloqueado_hasta'] ?? '');
            $primerIntentoRaw = (string) ($row['primer_intento_en'] ?? '');

            $ventanaVencida = $this->esVentanaVencida($primerIntentoRaw);
            $bloqueoVencido = $this->esBloqueoVencido($bloqueadoHastaRaw);
            $reiniciarVentana = $ventanaVencida || $bloqueoVencido;

            $nuevosIntentos = ($reiniciarVentana ? 0 : $intentosActuales) + 1;
            if ($nuevosIntentos >= (int) LOGIN_MAX_FAILED_ATTEMPTS) {
                $sqlBloqueadoHasta = "DATE_ADD(NOW(), INTERVAL " . (int) LOGIN_BLOCK_MINUTES . " MINUTE)";
            } else {
                $sqlBloqueadoHasta = "NULL";
            }

            $update = "UPDATE login_intentos
                       SET intentos = :intentos,
                           primer_intento_en = " . ($reiniciarVentana ? "NOW()" : "primer_intento_en") . ",
                           ultimo_intento_en = NOW(),
                           bloqueado_hasta = " . $sqlBloqueadoHasta . "
                       WHERE usuario = :usuario AND ip = :ip";

            $stmtUpdate = $this->pdo->prepare($update);
            $stmtUpdate->bindValue(':intentos', $nuevosIntentos, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':usuario', $usuario);
            $stmtUpdate->bindValue(':ip', $ip);
            $stmtUpdate->execute();

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Elimina intentos para usuario+IP.
     *
     * @param string $usuario
     * @param string $ip
     * @return void
     */
    public function limpiarIntentos(string $usuario, string $ip): void
    {
        $sql = "DELETE FROM login_intentos WHERE usuario = :usuario AND ip = :ip";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':ip'      => $ip
        ]);
    }

    /**
     * Purga filas antiguas para mantener la tabla liviana.
     *
     * @return int Filas eliminadas.
     */
    public function purgarExpirados(): int
    {
        $sql = "DELETE FROM login_intentos
                WHERE (bloqueado_hasta IS NOT NULL
                       AND bloqueado_hasta <= DATE_SUB(NOW(), INTERVAL 1 DAY))
                   OR (bloqueado_hasta IS NULL
                       AND ultimo_intento_en <= DATE_SUB(NOW(), INTERVAL " . (int) LOGIN_ATTEMPT_WINDOW_MINUTES . " MINUTE))";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Evalua vencimiento de bloqueo en SQL para evitar desajustes de zona horaria.
     */
    private function esBloqueoVencido(string $bloqueadoHasta): bool
    {
        $valor = trim($bloqueadoHasta);
        if ($valor === '') {
            return false;
        }

        $sql = "SELECT CASE WHEN :bloqueado_hasta <= NOW() THEN 1 ELSE 0 END";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':bloqueado_hasta' => $valor]);
        return (int) $stmt->fetchColumn() === 1;
    }

    /**
     * Evalua vencimiento de ventana en SQL para evitar desajustes de zona horaria.
     */
    private function esVentanaVencida(string $primerIntento): bool
    {
        $valor = trim($primerIntento);
        if ($valor === '') {
            return true;
        }

        $sql = "SELECT CASE
                    WHEN DATE_ADD(:primer_intento_en, INTERVAL " . (int) LOGIN_ATTEMPT_WINDOW_MINUTES . " MINUTE) <= NOW() THEN 1
                    ELSE 0
                END";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':primer_intento_en' => $valor]);
        return (int) $stmt->fetchColumn() === 1;
    }
}
