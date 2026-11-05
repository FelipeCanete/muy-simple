<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\SignedVia;
use App\Exceptions\Domain\InvalidSigningToken;
use App\Exceptions\Domain\SignerAlreadySigned;
use App\Exceptions\Domain\SignerNotFound;
use App\Exceptions\Domain\SigningTokenExpired;
use App\Models\Document;
use App\Models\Signer;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Support\Rut;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Toda la lógica del flujo de firma vive aquí: crear documentos,
 * registrar firmas (por enlace o por webhook) y recalcular el estado.
 * Los controladores solo traducen HTTP ↔ este servicio.
 */
class SigningService
{
    private const DISK = 'local'; // storage/app/private

    /**
     * @param  array<int, array{email: string, rut: string}>  $signers
     */
    public function createDocument(User $owner, string $title, UploadedFile $file, array $signers): CreatedDocument
    {
        // Nombre aleatorio en disco: el nombre original nunca se usa como ruta.
        $storedPath = $file->storeAs('documents', Str::uuid()->toString().'.pdf', self::DISK);

        if ($storedPath === false) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        try {
            return DB::transaction(function () use ($owner, $title, $file, $signers, $storedPath) {
                $document = Document::create([
                    'created_by' => $owner->getKey(),
                    'title' => $title,
                    'original_name' => $this->safeFileName($file->getClientOriginalName()),
                    'stored_path' => $storedPath,
                    'mime' => 'application/pdf',
                    'size' => $file->getSize(),
                    'sha256' => hash_file('sha256', Storage::disk(self::DISK)->path($storedPath)),
                    'status' => DocumentStatus::Pendiente,
                ]);

                $plainTokens = [];
                $expiresAt = now()->addDays(config('signing.token_ttl_days'));

                foreach ($signers as $data) {
                    $token = bin2hex(random_bytes(32));

                    $signer = $document->signers()->create([
                        'email' => Str::lower($data['email']),
                        'rut' => Rut::normalize($data['rut']),
                        'token_hash' => Signer::hashToken($token),
                        'token_expires_at' => $expiresAt,
                    ]);

                    $plainTokens[$signer->id] = $token;
                }

                Log::info('documento.creado', [
                    'document_id' => $document->id,
                    'signers' => count($signers),
                    'user_id' => $owner->getKey(),
                ]);

                return new CreatedDocument($document->load('signers'), $plainTokens);
            });
        } catch (Throwable $e) {
            // Si falla la BD no dejamos archivos huérfanos.
            Storage::disk(self::DISK)->delete($storedPath);
            throw $e;
        }
    }

    /**
     * Busca al firmante por su token en claro. No valida expiración:
     * eso depende de la acción (ver o firmar).
     */
    public function findSignerByToken(string $plainToken): Signer
    {
        $signer = Signer::with('document')
            ->where('token_hash', Signer::hashToken($plainToken))
            ->first();

        if ($signer === null) {
            throw new InvalidSigningToken;
        }

        return $signer;
    }

    /** Firma realizada por el propio firmante desde su enlace único. */
    public function signByToken(string $plainToken, ?string $ip): Signer
    {
        return DB::transaction(function () use ($plainToken, $ip) {
            $signer = Signer::where('token_hash', Signer::hashToken($plainToken))
                ->lockForUpdate()
                ->first();

            if ($signer === null) {
                throw new InvalidSigningToken;
            }

            // Primero "ya firmó": es más útil para el usuario que "expirado".
            if ($signer->isSigned()) {
                throw new SignerAlreadySigned;
            }

            if ($signer->isExpired()) {
                throw new SigningTokenExpired;
            }

            return $this->markAsSigned($signer, SignedVia::Link, $ip);
        });
    }

    /**
     * Callback del proveedor externo. Es idempotente:
     *  - el mismo event_id procesado dos veces no cambia nada;
     *  - un firmante que ya firmó se responde como éxito sin modificarlo.
     */
    public function signFromWebhook(string $eventId, string $signerId): WebhookResult
    {
        return DB::transaction(function () use ($eventId, $signerId) {
            $previous = WebhookEvent::where('event_id', $eventId)->first();

            if ($previous !== null) {
                $signer = Signer::with('document')->findOrFail($previous->signer_id);

                return new WebhookResult($signer, duplicateEvent: true, alreadySigned: true);
            }

            $signer = Signer::lockForUpdate()->find($signerId);

            if ($signer === null) {
                throw new SignerNotFound;
            }

            WebhookEvent::create([
                'event_id' => $eventId,
                'signer_id' => $signer->id,
                'received_at' => now(),
            ]);

            if ($signer->isSigned()) {
                return new WebhookResult($signer->load('document'), duplicateEvent: false, alreadySigned: true);
            }

            // El webhook ignora la expiración del enlace: el proveedor ya
            // verificó la firma por su lado; la expiración aplica al enlace.
            $signer = $this->markAsSigned($signer, SignedVia::Webhook, null);

            return new WebhookResult($signer, duplicateEvent: false, alreadySigned: false);
        });
    }

    /**
     * Recalcula el estado del documento a partir de sus firmantes.
     * Debe llamarse dentro de una transacción.
     */
    public function recalculateStatus(Document $document): Document
    {
        $total = $document->signers()->count();
        $signed = $document->signers()->whereNotNull('signed_at')->count();

        $document->status = DocumentStatus::fromCounts($signed, $total);
        $document->save();

        return $document;
    }

    private function markAsSigned(Signer $signer, SignedVia $via, ?string $ip): Signer
    {
        $signer->forceFill([
            'signed_at' => now(),
            'signed_via' => $via,
            'signed_ip' => $ip,
        ])->save();

        $document = Document::lockForUpdate()->findOrFail($signer->document_id);
        $previous = $document->status;
        $this->recalculateStatus($document);

        // Sin token ni RUT completo en los logs (OWASP A09).
        Log::info('documento.firmado', [
            'document_id' => $document->id,
            'signer_id' => $signer->id,
            'rut' => Rut::mask($signer->rut),
            'via' => $via->value,
            'status_from' => $previous->value,
            'status_to' => $document->status->value,
        ]);

        return $signer->setRelation('document', $document);
    }

    private function safeFileName(string $name): string
    {
        // Sin rutas ni caracteres de control; largo acotado.
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? 'documento.pdf';

        return Str::limit($name, 200, '') ?: 'documento.pdf';
    }
}
