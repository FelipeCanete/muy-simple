<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignatureWebhookRequest;
use App\Services\SigningService;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/webhooks/signatures
 *
 * Callback de un proveedor externo que informa que un firmante firmó.
 * Autenticado por HMAC (middleware VerifyWebhookSignature) e idempotente.
 */
class SignatureWebhookController extends Controller
{
    public function __construct(private readonly SigningService $signing) {}

    public function __invoke(SignatureWebhookRequest $request): JsonResponse
    {
        $result = $this->signing->signFromWebhook(
            $request->validated('event_id'),
            $request->validated('signer_id'),
        );

        $document = $result->signer->document;

        return response()->json([
            'data' => [
                'event_id' => $request->validated('event_id'),
                'signer_id' => $result->signer->id,
                'signer_status' => $result->signer->statusLabel(),
                'document_id' => $document->id,
                'document_status' => $document->status->value,
                'duplicate_event' => $result->duplicateEvent,
                'already_signed' => $result->alreadySigned,
            ],
        ]);
    }
}
