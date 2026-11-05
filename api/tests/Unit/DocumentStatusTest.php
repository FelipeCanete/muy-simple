<?php

use App\Enums\DocumentStatus;

it('deriva el estado según cuántos firmantes firmaron', function (int $signed, int $total, DocumentStatus $expected) {
    expect(DocumentStatus::fromCounts($signed, $total))->toBe($expected);
})->with([
    'nadie firmó' => [0, 3, DocumentStatus::Pendiente],
    'uno de tres' => [1, 3, DocumentStatus::FirmadoParcial],
    'dos de tres' => [2, 3, DocumentStatus::FirmadoParcial],
    'todos' => [3, 3, DocumentStatus::Completo],
    'único firmante pendiente' => [0, 1, DocumentStatus::Pendiente],
    'único firmante firmó' => [1, 1, DocumentStatus::Completo],
]);

it('rechaza conteos imposibles', function (int $signed, int $total) {
    DocumentStatus::fromCounts($signed, $total);
})->with([
    'sin firmantes' => [0, 0],
    'más firmas que firmantes' => [4, 3],
    'firmas negativas' => [-1, 3],
])->throws(InvalidArgumentException::class);

it('usa los valores y etiquetas en español', function () {
    expect(DocumentStatus::Pendiente->value)->toBe('pendiente')
        ->and(DocumentStatus::FirmadoParcial->value)->toBe('firmado_parcial')
        ->and(DocumentStatus::Completo->value)->toBe('completo')
        ->and(DocumentStatus::FirmadoParcial->label())->toBe('firmado parcial');
});
