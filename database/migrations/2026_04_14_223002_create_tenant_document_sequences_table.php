<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 64);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_document_sequences');
    }
};
