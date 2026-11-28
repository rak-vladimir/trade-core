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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('quantity', 20, 4)->default(0);

            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
            $table->index('product_id');
        });

        DB::statement('
            ALTER TABLE stocks
            ADD CONSTRAINT stocks_quantity_non_negative
            CHECK (quantity >= 0)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
