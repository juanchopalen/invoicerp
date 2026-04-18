<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('source_system', 128);
            $table->string('external_reference', 255);
            $table->string('document_number', 64);

            $table->string('document_type', 64)->default('invoice');
            $table->string('status', 32);

            $table->decimal('subtotal', 18, 4);
            $table->decimal('tax_total', 18, 4);
            $table->decimal('total', 18, 4);

            $table->char('currency', 3)->default('VES');
            $table->unsignedSmallInteger('schema_version')->default(1);

            $table->string('idempotency_payload_hash', 64)->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->string('hash', 128);

            $table->timestamps();

            $table->unique(['tenant_id', 'source_system', 'external_reference'], 'fiscal_documents_idempotency_uq');
            $table->index(['tenant_id', 'document_type', 'document_number']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_documents');
    }
};
