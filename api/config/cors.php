<?php

/*
| El front se sirve desde el mismo origen (nginx hace proxy de /api),
| por lo que no se habilita CORS para ningún origen externo (OWASP A05).
*/

return [
    'paths' => [],
    'allowed_methods' => [],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
