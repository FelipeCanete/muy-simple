<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListDocumentsRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\SigningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly SigningService $signing) {}

    /** GET /api/documents — lista paginada con estado y avance de firmas. */
    public function index(ListDocumentsRequest $request): AnonymousResourceCollection
    {
        $documents = Document::ownedBy($request->user())
            ->withSignatureCounts()
            ->when($request->validated('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return DocumentResource::collection($documents);
    }

    /** POST /api/documents — sube el PDF y genera un enlace por firmante. */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $created = $this->signing->createDocument(
            $request->user(),
            $request->validated('title'),
            $request->file('file'),
            $request->validated('signers'),
        );

        $links = $created->document->signers->map(fn ($signer) => [
            'signer_id' => $signer->id,
            'email' => $signer->email,
            'url' => $this->signingUrl($created->plainTokens[$signer->id]),
        ])->values();

        return (new DocumentResource($created->document))
            ->additional([
                // Los enlaces se muestran UNA sola vez: en la BD solo queda el hash.
                'signing_links' => $links,
            ])
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/documents/{id} */
    public function show(Request $request, string $id): DocumentResource
    {
        $document = $this->findOwned($request, $id)->load('signers');

        return new DocumentResource($document);
    }

    /** GET /api/documents/{id}/file */
    public function file(Request $request, string $id): StreamedResponse
    {
        $document = $this->findOwned($request, $id);

        return Storage::disk('local')->response(
            $document->stored_path,
            $document->original_name,
            ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'],
        );
    }

    /**
     * Si el documento no existe o es de otro usuario respondemos 404 en
     * ambos casos: no revelamos qué IDs existen.
     */
    private function findOwned(Request $request, string $id): Document
    {
        return Document::ownedBy($request->user())
            ->withSignatureCounts()
            ->whereKey($id)
            ->firstOrFail();
    }

    private function signingUrl(string $token): string
    {
        return rtrim(config('app.url'), '/').'/firmar/'.$token;
    }
}
