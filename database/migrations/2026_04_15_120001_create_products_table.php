<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('sku', 64)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 18, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->char('currency', 3)->default('VES');
            $table->string('unit', 32)->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
