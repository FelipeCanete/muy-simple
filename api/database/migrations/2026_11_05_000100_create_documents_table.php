<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('original_name');
            // Ruta interna en el disco privado; nunca se expone en la API.
            $table->string('stored_path');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->char('sha256', 64);
            // pendiente | firmado_parcial | completo (App\Enums\DocumentStatus)
            $table->string('status', 20)->default('pendiente')->index();
            $table->timestamps();

            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
