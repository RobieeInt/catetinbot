<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->index();
            $table->string('name');
            $table->unsignedBigInteger('amount');
            $table->string('category');
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->integer('day_of_month'); // 1–31
            $table->integer('reminder_days_before')->default(2);
            $table->boolean('active')->default(true);
            $table->string('last_charged_month')->nullable(); // format YYYY-MM
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
