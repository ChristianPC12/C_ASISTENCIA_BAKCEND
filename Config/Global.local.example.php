<?php
declare(strict_types=1);

/**
 * Copiar como Config/Global.local.php si necesitas overrides locales.
 * Este archivo de ejemplo se puede versionar; el .local.php no.
 *
 * @return array<string, string|int>
 */
return [
    // --- Brevo (correo transaccional para credenciales temporales) ---
    // Crea tu API key en Brevo y pegala aqui.
    'BREVO_API_KEY' => '',

    // Correo remitente verificado en Brevo.
    'BREVO_SENDER_EMAIL' => '',

    // Nombre remitente mostrado al destinatario.
    'BREVO_SENDER_NAME' => 'C_ASISTENCIA',

    // Endpoint SMTP API de Brevo (dejar default normalmente).
    'BREVO_API_URL' => 'https://api.brevo.com/v3/smtp/email',

    // Timeout HTTP para llamada a Brevo.
    'BREVO_TIMEOUT_SECONDS' => 10,
];
