<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->tinyInteger('notified_20')->default(0)->after('notified');
            $table->tinyInteger('notified_10')->default(0)->after('notified_20');
            $table->tinyInteger('notified_5')->default(0)->after('notified_10');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['notified_20', 'notified_10', 'notified_5']);
        });
    }
};
