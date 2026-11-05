<?php

use App\Support\Rut;

describe('Rut::isValid', function () {
    it('acepta RUTs válidos en distintos formatos', function (string $rut) {
        expect(Rut::isValid($rut))->toBeTrue();
    })->with([
        'con puntos y guion' => '12.345.678-5',
        'sin puntos' => '12345678-5',
        'sin guion' => '123456785',
        'DV K mayúscula' => '10.000.013-K',
        'DV k minúscula' => '10000013-k',
        'DV 0' => '10000004-0',
        'cuerpo de 7 dígitos' => '7.654.321-6',
        'con espacios alrededor' => ' 11111111-1 ',
    ]);

    it('rechaza RUTs inválidos', function (string $rut) {
        expect(Rut::isValid($rut))->toBeFalse();
    })->with([
        'DV incorrecto' => '12345678-9',
        'vacío' => '',
        'solo DV' => '5',
        'letras en el cuerpo' => '12A45678-5',
        'K en el cuerpo' => '1K345678-5',
        'cuerpo demasiado corto' => '123456-0',
        'cuerpo demasiado largo' => '123456789-1',
        'caracteres extraños' => '12345678/5',
        'inyección' => "12345678-5'; DROP TABLE signers;--",
    ]);
});

describe('Rut::normalize', function () {
    it('quita puntos, agrega guion y pasa la K a mayúscula', function (string $input, string $expected) {
        expect(Rut::normalize($input))->toBe($expected);
    })->with([
        ['12.345.678-5', '12345678-5'],
        ['123456785', '12345678-5'],
        ['10000013-k', '10000013-K'],
    ]);
});

describe('Rut::checkDigit', function () {
    it('calcula el dígito verificador con módulo 11', function (string $body, string $dv) {
        expect(Rut::checkDigit($body))->toBe($dv);
    })->with([
        ['12345678', '5'],
        ['10000013', 'K'],
        ['10000004', '0'],
        ['11111111', '1'],
    ]);
});

it('enmascara el RUT para los logs', function () {
    expect(Rut::mask('12.345.678-5'))->toBe('****5678-5')
        ->and(Rut::mask('7654321-6'))->toBe('***4321-6');
});
