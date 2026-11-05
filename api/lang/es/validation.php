<?php

// Solo las reglas que usa esta API; el resto cae al idioma de respaldo (en).
return [
    'array' => 'El campo :attribute debe ser una lista.',
    'distinct' => 'El campo :attribute tiene un valor duplicado.',
    'email' => 'El campo :attribute debe ser un email válido.',
    'enum' => 'El valor seleccionado en :attribute no es válido.',
    'extensions' => 'El campo :attribute debe tener una de estas extensiones: :values.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El campo :attribute no puede superar los :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'mimetypes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe tener al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    'attributes' => [
        'email' => 'email',
        'password' => 'contraseña',
        'status' => 'estado',
        'per_page' => 'resultados por página',
        'event_id' => 'event_id',
        'signer_id' => 'signer_id',
    ],
];
