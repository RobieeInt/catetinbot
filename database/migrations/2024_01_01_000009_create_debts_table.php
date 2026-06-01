<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->index();
            $table->enum('type', ['utang', 'piutang']);
            $table->string('person');
            $table->unsignedBigInteger('amount');
            $table->text('note')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('settled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
