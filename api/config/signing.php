<?php

/*
| Configuración del flujo de firma.
| Los secretos llegan solo por variables de entorno (nunca en el repo).
*/

return [

    // Días que dura un enlace de firma antes de expirar.
    'token_ttl_days' => (int) env('SIGNING_TOKEN_TTL_DAYS', 7),

    // Máximo de firmantes por documento y tamaño máximo del PDF (KB).
    'max_signers' => (int) env('SIGNING_MAX_SIGNERS', 20),
    'max_file_kb' => (int) env('SIGNING_MAX_FILE_KB', 10240),

    'webhook' => [
        // Secreto compartido para validar la firma HMAC-SHA256 del callback.
        'secret' => env('WEBHOOK_SECRET'),
        // Ventana aceptada (segundos) entre X-Timestamp y la hora del servidor.
        'tolerance_seconds' => (int) env('WEBHOOK_TOLERANCE_SECONDS', 300),
        // URL a la que apunta `php artisan webhook:simulate` (solo demo).
        'demo_url' => env('WEBHOOK_DEMO_URL', 'http://127.0.0.1:8000/api/webhooks/signatures'),
    ],

    'demo_user' => [
        'email' => env('DEMO_USER_EMAIL', 'admin@demo.cl'),
        'password' => env('DEMO_USER_PASSWORD'),
    ],

];
