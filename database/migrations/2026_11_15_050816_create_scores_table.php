<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('hand');
            $table->unsignedInteger('bet');
            $table->unsignedInteger('payout');
            $table->unsignedInteger('credits_after');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('credits')->default(100);
            $table->unsignedInteger('best_credits')->default(100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['credits', 'best_credits']);
        });
    }
};
