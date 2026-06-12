<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_ideas', function (Blueprint $table) {
            $table->id();
            $table->text('idea_text');
            $table->string('title')->nullable();
            $table->longText('concept_prompt')->nullable();
            $table->longText('script_prompt')->nullable();
            $table->longText('image_prompt_instruction')->nullable();
            $table->longText('main_prompt')->nullable();
            $table->longText('script')->nullable();
            $table->longText('image_prompt')->nullable();
            $table->string('image_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('captions_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('status')->default('draft');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_ideas');
    }
};
