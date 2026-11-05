<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->string('email', 254);
            // RUT normalizado: 12345678-K
            $table->string('rut', 12);
            // Solo guardamos el SHA-256 del token: el token en claro nunca toca la BD.
            $table->char('token_hash', 64)->unique();
            $table->timestamp('token_expires_at');
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_via', 10)->nullable(); // link | webhook
            $table->string('signed_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'email']);
            $table->unique(['document_id', 'rut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signers');
    }
};
