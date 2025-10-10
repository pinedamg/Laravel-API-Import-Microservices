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
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('base_plan_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('title');
            $table->string('sell_mode');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->decimal('min_price', 8, 2)->nullable();
            $table->decimal('max_price', 8, 2)->nullable();
            $table->string('status')->default('active');

            $table->timestamps();

            $table->unique(['base_plan_id', 'plan_id']);
            $table->index('starts_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
