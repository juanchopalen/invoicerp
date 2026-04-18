<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_document_id')->constrained('fiscal_documents')->cascadeOnDelete();

            $table->unsignedInteger('line_number');
            $table->string('description');
            $table->decimal('qty', 18, 6);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);

            $table->decimal('line_subtotal', 18, 4);
            $table->decimal('line_tax', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4);

            $table->json('totals')->nullable();

            $table->timestamps();

            $table->unique(['fiscal_document_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_document_items');
    }
};
