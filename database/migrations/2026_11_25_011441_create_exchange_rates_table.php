<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('base_currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();

            $table->foreignId('quote_currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();

            $table->decimal('rate', 20, 10);
            $table->timestamp('effective_at');

            $table->timestamps();

            $table->unique([
                'base_currency_id',
                'quote_currency_id',
                'effective_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
