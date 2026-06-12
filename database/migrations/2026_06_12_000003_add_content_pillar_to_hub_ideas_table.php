<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_ideas', function (Blueprint $table) {
            $table->string('content_pillar')->nullable()->after('title');
            $table->index('content_pillar');
        });
    }

    public function down(): void
    {
        Schema::table('hub_ideas', function (Blueprint $table) {
            $table->dropIndex(['content_pillar']);
            $table->dropColumn('content_pillar');
        });
    }
};
