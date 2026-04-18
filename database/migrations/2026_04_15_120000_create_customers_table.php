<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('legal_name');
            $table->string('tax_id', 32);
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->text('address');
            $table->string('city', 128)->nullable();
            $table->string('municipality', 128);
            $table->string('state', 128);
            $table->char('country', 2)->default('VE');

            $table->timestamps();

            $table->index(['tenant_id', 'tax_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
