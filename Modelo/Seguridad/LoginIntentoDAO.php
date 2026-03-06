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
        $sql = "SELECT primer_intento_en, bloqueado_hasta
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

        $ahora = new DateTimeImmutable('now');
        $bloqueadoHasta = $this->parseTimestamp($row['bloqueado_hasta'] ?? null);

        if ($bloqueadoHasta !== null) {
            if ($bloqueadoHasta > $ahora) {
                return [
                    'bloqueado' => true,
                    'segundos_restantes' => max(0, $bloqueadoHasta->getTimestamp() - $ahora->getTimestamp())
                ];
            }

            // Bloqueo vencido: limpiar estado para permitir nuevos intentos.
            $this->limpiarIntentos($usuario, $ip);
            return [
                'bloqueado' => false,
                'segundos_restantes' => 0
            ];
        }

        // Ventana de intentos vencida: limpiar para no acumular historico innecesario.
        $primerIntento = $this->parseTimestamp($row['primer_intento_en'] ?? null);
        if ($primerIntento !== null) {
            $finVentana = $primerIntento->modify('+' . (int) LOGIN_ATTEMPT_WINDOW_MINUTES . ' minutes');
            if ($finVentana <= $ahora) {
                $this->limpiarIntentos($usuario, $ip);
            }
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

            $ahora = new DateTimeImmutable('now');

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
            $primerIntento = $this->parseTimestamp($row['primer_intento_en'] ?? null) ?? $ahora;
            $bloqueadoHasta = $this->parseTimestamp($row['bloqueado_hasta'] ?? null);

            // Si el bloqueo ya vencio, reiniciar ventana.
            if ($bloqueadoHasta !== null && $bloqueadoHasta <= $ahora) {
                $intentosActuales = 0;
                $primerIntento = $ahora;
                $bloqueadoHasta = null;
            }

            $finVentana = $primerIntento->modify('+' . (int) LOGIN_ATTEMPT_WINDOW_MINUTES . ' minutes');
            if ($finVentana <= $ahora) {
                $intentosActuales = 0;
                $primerIntento = $ahora;
                $bloqueadoHasta = null;
            }

            $nuevosIntentos = $intentosActuales + 1;
            if ($nuevosIntentos >= (int) LOGIN_MAX_FAILED_ATTEMPTS) {
                $bloqueadoHasta = $ahora->modify('+' . (int) LOGIN_BLOCK_MINUTES . ' minutes');
            }

            $update = "UPDATE login_intentos
                       SET intentos = :intentos,
                           primer_intento_en = :primer_intento_en,
                           ultimo_intento_en = NOW(),
                           bloqueado_hasta = :bloqueado_hasta
                       WHERE usuario = :usuario AND ip = :ip";

            $stmtUpdate = $this->pdo->prepare($update);
            $stmtUpdate->bindValue(':intentos', $nuevosIntentos, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':primer_intento_en', $primerIntento->format('Y-m-d H:i:s'));

            if ($bloqueadoHasta === null) {
                $stmtUpdate->bindValue(':bloqueado_hasta', null, PDO::PARAM_NULL);
            } else {
                $stmtUpdate->bindValue(':bloqueado_hasta', $bloqueadoHasta->format('Y-m-d H:i:s'));
            }

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
     * @param mixed $value
     * @return DateTimeImmutable|null
     */
    private function parseTimestamp($value): ?DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
