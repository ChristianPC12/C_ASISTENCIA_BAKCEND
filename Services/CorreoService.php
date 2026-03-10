<?php
declare(strict_types=1);

/**
 * Clase CorreoService
 *
 * Envio opcional de notificaciones por correo.
 * Si no hay configuracion de proveedor, retorna estado "no enviado" sin lanzar excepcion.
 */
final class CorreoService
{
    /**
     * Intenta enviar credenciales de ADMIN temporal via Brevo.
     *
     * @param string $destinoCorreo
     * @param string $destinoNombre
     * @param string $usuario
     * @param string $passwordTemporal
     * @param string $codigoInstancia
     * @param string $expiraEn
     * @return array<string, mixed>
     */
    public function enviarCredencialesAdminTemporal(
        string $destinoCorreo,
        string $destinoNombre,
        string $usuario,
        string $passwordTemporal,
        string $codigoInstancia,
        string $expiraEn
    ): array {
        if (!function_exists('curl_init')) {
            return [
                'enviado' => false,
                'proveedor' => 'brevo',
                'detalle' => 'No se pudo enviar correo: cURL no disponible.'
            ];
        }

        $apiKey = trim((string) getenv('BREVO_API_KEY'));
        $senderEmail = trim((string) getenv('BREVO_SENDER_EMAIL'));
        $senderName = trim((string) getenv('BREVO_SENDER_NAME'));

        if ($senderName === '') {
            $senderName = 'C_ASISTENCIA';
        }

        if ($apiKey === '' || $senderEmail === '' || filter_var($senderEmail, FILTER_VALIDATE_EMAIL) === false) {
            return [
                'enviado' => false,
                'proveedor' => 'brevo',
                'detalle' => 'No se pudo enviar correo: configuracion Brevo no disponible.'
            ];
        }

        if (filter_var($destinoCorreo, FILTER_VALIDATE_EMAIL) === false) {
            return [
                'enviado' => false,
                'proveedor' => 'brevo',
                'detalle' => 'No se pudo enviar correo: destino invalido.'
            ];
        }

        $subject = 'Credenciales temporales - C_ASISTENCIA';
        $html = $this->buildHtmlCredenciales(
            $destinoNombre,
            $usuario,
            $passwordTemporal,
            $codigoInstancia,
            $expiraEn
        );

        $payload = [
            'sender' => [
                'name' => $senderName,
                'email' => $senderEmail
            ],
            'to' => [
                [
                    'email' => $destinoCorreo,
                    'name' => $destinoNombre !== '' ? $destinoNombre : $destinoCorreo
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $html
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonPayload === false) {
            return [
                'enviado' => false,
                'proveedor' => 'brevo',
                'detalle' => 'No se pudo enviar correo: serializacion invalida.'
            ];
        }

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        if ($ch === false) {
            return [
                'enviado' => false,
                'proveedor' => 'brevo',
                'detalle' => 'No se pudo enviar correo: inicializacion de cliente fallo.'
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlErr !== '') {
            return [
                'enviado' => false,
                'proveedor' => 'brevo',
                'detalle' => 'No se pudo enviar correo: error de transporte.'
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'enviado' => true,
                'proveedor' => 'brevo',
                'detalle' => 'Correo enviado correctamente.'
            ];
        }

        return [
            'enviado' => false,
            'proveedor' => 'brevo',
            'detalle' => 'No se pudo enviar correo: proveedor rechazo la solicitud (HTTP ' . $httpCode . ').'
        ];
    }

    /**
     * Construye contenido HTML del correo de credenciales temporales.
     *
     * @param string $nombre
     * @param string $usuario
     * @param string $passwordTemporal
     * @param string $codigoInstancia
     * @param string $expiraEn
     * @return string
     */
    private function buildHtmlCredenciales(
        string $nombre,
        string $usuario,
        string $passwordTemporal,
        string $codigoInstancia,
        string $expiraEn
    ): string {
        $nombreSafe = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $usuarioSafe = htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8');
        $passwordSafe = htmlspecialchars($passwordTemporal, ENT_QUOTES, 'UTF-8');
        $codigoSafe = htmlspecialchars($codigoInstancia, ENT_QUOTES, 'UTF-8');
        $expiraSafe = htmlspecialchars($expiraEn, ENT_QUOTES, 'UTF-8');

        return '<p>Hola ' . $nombreSafe . ',</p>'
            . '<p>Se creo tu acceso temporal a C_ASISTENCIA.</p>'
            . '<ul>'
            . '<li><strong>Usuario:</strong> ' . $usuarioSafe . '</li>'
            . '<li><strong>Password temporal:</strong> ' . $passwordSafe . '</li>'
            . '<li><strong>Codigo de instancia:</strong> ' . $codigoSafe . '</li>'
            . '<li><strong>Vence:</strong> ' . $expiraSafe . '</li>'
            . '</ul>'
            . '<p>Por seguridad, cambia la contrasena al ingresar.</p>';
    }
}

