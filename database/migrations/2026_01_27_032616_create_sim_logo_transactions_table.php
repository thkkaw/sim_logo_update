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
        Schema::create('sim_logo_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn')->index(); // Added index for faster lookups
            $table->string('transaction_spn')->nullable();
            $table->string('transaction_pnn')->nullable();
            $table->string('spn_pnn')->nullable();
            $table->unsignedTinyInteger('count')->default(0);
            $table->enum('status', ['fail', 'success', 'pending', '404'])->default('pending');
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sim_logo_transactions');
    }
};
