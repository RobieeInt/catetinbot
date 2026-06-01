<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->index();
            $table->unsignedBigInteger('from_wallet_id')->index();
            $table->unsignedBigInteger('to_wallet_id')->index();
            $table->unsignedBigInteger('amount');
            $table->dateTime('transfer_date');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transfers');
    }
};
