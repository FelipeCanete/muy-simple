<?php

namespace App\Http\Requests;

use App\Rules\Rut;
use App\Support\Rut as RutSupport;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalizamos antes de validar para que "distinct" detecte
     * duplicados escritos distinto (12.345.678-5 vs 12345678-5, mayúsculas en email).
     */
    protected function prepareForValidation(): void
    {
        $signers = $this->input('signers');

        if (! is_array($signers)) {
            return;
        }

        $this->merge([
            'title' => is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title'),
            'signers' => array_map(function ($signer) {
                if (! is_array($signer)) {
                    return $signer;
                }

                if (isset($signer['email']) && is_string($signer['email'])) {
                    $signer['email'] = mb_strtolower(trim($signer['email']));
                }

                if (isset($signer['rut']) && is_string($signer['rut']) && RutSupport::isValid($signer['rut'])) {
                    $signer['rut'] = RutSupport::normalize($signer['rut']);
                }

                return $signer;
            }, $signers),
        ]);
    }

    public function rules(): array
    {
        $maxKb = config('signing.max_file_kb');
        $maxSigners = config('signing.max_signers');

        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            // mimetypes revisa el contenido real del archivo (magic bytes), no solo la extensión.
            'file' => ['required', 'file', 'extensions:pdf', 'mimetypes:application/pdf', "max:{$maxKb}"],
            'signers' => ['required', 'array', 'min:1', "max:{$maxSigners}"],
            'signers.*' => ['required', 'array:email,rut'],
            'signers.*.email' => ['required', 'string', 'email:rfc', 'max:254', 'distinct'],
            'signers.*.rut' => ['required', 'string', 'max:12', new Rut, 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'file' => 'archivo',
            'signers' => 'firmantes',
            'signers.*.email' => 'email del firmante',
            'signers.*.rut' => 'RUT del firmante',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimetypes' => 'El archivo debe ser un PDF válido.',
            'file.extensions' => 'El archivo debe tener extensión .pdf.',
            'signers.*.email.distinct' => 'Hay emails de firmantes repetidos.',
            'signers.*.rut.distinct' => 'Hay RUTs de firmantes repetidos.',
        ];
    }
}
