<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_posts', function (Blueprint $table) {
            // Zeitpunkt, zu dem der/die Ersteller:in zuletzt die Kommentare gesehen hat
            // (analog zu chat_participants.last_read_at / group_members.last_read_at).
            $table->timestamp('comments_read_at')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('board_posts', function (Blueprint $table) {
            $table->dropColumn('comments_read_at');
        });
    }
};
