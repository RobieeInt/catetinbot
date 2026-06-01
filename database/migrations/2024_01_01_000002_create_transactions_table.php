<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->index();
            $table->enum('type', ['expense', 'income']);
            $table->unsignedBigInteger('wallet_id')->nullable()->index();
            $table->date('date')->index();
            $table->unsignedBigInteger('total');
            $table->string('category');
            $table->string('merchant')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
