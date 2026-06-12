<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_ideas', function (Blueprint $table) {
            $table->longText('chatgpt_development_response')->nullable()->after('image_prompt');
        });
    }

    public function down(): void
    {
        Schema::table('hub_ideas', function (Blueprint $table) {
            $table->dropColumn('chatgpt_development_response');
        });
    }
};
