<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_documents', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('tenant_id')->constrained('customers')->nullOnDelete();

            $table->string('customer_legal_name')->nullable()->after('customer_id');
            $table->string('customer_tax_id', 32)->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 64)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_city', 128)->nullable();
            $table->string('customer_municipality', 128)->nullable();
            $table->string('customer_state', 128)->nullable();
            $table->char('customer_country', 2)->nullable();
        });

        Schema::table('fiscal_document_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('fiscal_document_id')->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_document_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });

        Schema::table('fiscal_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');

            $table->dropColumn([
                'customer_legal_name',
                'customer_tax_id',
                'customer_email',
                'customer_phone',
                'customer_address',
                'customer_city',
                'customer_municipality',
                'customer_state',
                'customer_country',
            ]);
        });
    }
};
