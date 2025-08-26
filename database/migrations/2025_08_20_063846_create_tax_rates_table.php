<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('tax_type', ['standard', 'reduced', 'exempt', 'non_taxable'])->comment('税区分');
            $table->decimal('rate', 5, 2)->comment('税率（%）');
            $table->timestamps();

            // インデックス
            $table->index('tax_type', 'idx_tax_rates_tax_type');

            // テーブルコメント
            $table->comment('税率マスター');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
