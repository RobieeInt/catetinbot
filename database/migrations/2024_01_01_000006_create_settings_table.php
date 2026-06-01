<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->unsignedBigInteger('daily_budget')->default(0);
            $table->unsignedBigInteger('weekly_budget')->default(0);
            $table->unsignedBigInteger('monthly_budget')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
