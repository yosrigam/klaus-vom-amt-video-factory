<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_ideas', function (Blueprint $table) {
            $table->dropColumn([
                'concept_prompt',
                'script_prompt',
                'image_prompt_instruction',
                'main_prompt',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('hub_ideas', function (Blueprint $table) {
            $table->longText('concept_prompt')->nullable();
            $table->longText('script_prompt')->nullable();
            $table->longText('image_prompt_instruction')->nullable();
            $table->longText('main_prompt')->nullable();
        });
    }
};
