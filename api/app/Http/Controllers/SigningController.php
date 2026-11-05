<?php

namespace App\Http\Controllers;

use App\Exceptions\Domain\SigningTokenExpired;
use App\Http\Resources\SigningPageResource;
use App\Services\SigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoints públicos del firmante. El token del enlace es la credencial:
 * da acceso solo a ese firmante y a su documento.
 */
class SigningController extends Controller
{
    public function __construct(private readonly SigningService $signing) {}

    /** GET /api/sign/{token} — datos para mostrar la pantalla de firma. */
    public function show(string $token): SigningPageResource
    {
        $signer = $this->signing->findSignerByToken($token);

        // Si ya firmó mostramos su estado aunque el enlace haya vencido.
        if (! $signer->isSigned() && $signer->isExpired()) {
            throw new SigningTokenExpired;
        }

        return new SigningPageResource($signer);
    }

    /** GET /api/sign/{token}/file — el PDF que se va a firmar. */
    public function file(string $token): StreamedResponse
    {
        $signer = $this->signing->findSignerByToken($token);

        if (! $signer->isSigned() && $signer->isExpired()) {
            throw new SigningTokenExpired;
        }

        $document = $signer->document;

        return Storage::disk('local')->response(
            $document->stored_path,
            $document->original_name,
            ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'],
        );
    }

    /** POST /api/sign/{token} — registra la firma. */
    public function sign(Request $request, string $token): SigningPageResource
    {
        $signer = $this->signing->signByToken($token, $request->ip());

        return new SigningPageResource($signer);
    }
}
