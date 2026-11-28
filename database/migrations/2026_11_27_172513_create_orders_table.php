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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('number', 30)->unique();

            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status', 20)->default('draft');
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        DB::statement("
            ALTER TABLE orders
            ADD CONSTRAINT orders_status_check
            CHECK (status IN ('draft', 'confirmed', 'shipped', 'cancelled'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
