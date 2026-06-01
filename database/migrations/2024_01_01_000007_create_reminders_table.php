<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->index();
            $table->string('task');
            $table->dateTime('remind_at')->index();
            $table->enum('repeat', ['none', 'daily', 'weekly', 'monthly'])->default('none');
            $table->boolean('notified')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
