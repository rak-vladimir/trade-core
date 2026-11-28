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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('from_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('to_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->string('type', 20);

            $table->decimal('quantity', 20, 4);

            $table->string('reference')->nullable();
            $table->text('comment')->nullable();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
            $table->index(['from_warehouse_id', 'created_at']);
            $table->index(['to_warehouse_id', 'created_at']);
        });

        DB::statement("
            ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_quantity_positive
            CHECK (quantity > 0)
        ");

        DB::statement("
            ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_type_valid
            CHECK (type IN ('receipt', 'write_off', 'transfer'))
        ");

        DB::statement("
            ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_warehouses_valid
            CHECK (
                (type = 'receipt'
                    AND from_warehouse_id IS NULL
                    AND to_warehouse_id IS NOT NULL)
                OR
                (type = 'write_off'
                    AND from_warehouse_id IS NOT NULL
                    AND to_warehouse_id IS NULL)
                OR
                (type = 'transfer'
                    AND from_warehouse_id IS NOT NULL
                    AND to_warehouse_id IS NOT NULL
                    AND from_warehouse_id <> to_warehouse_id)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
