<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('content_pillar');
            $table->string('title');
            $table->string('hook');
            $table->text('short_concept');
            $table->string('status')->default('draft');
            $table->longText('script')->nullable();
            $table->longText('image_prompt')->nullable();
            $table->longText('voice_text')->nullable();
            $table->string('captions_path')->nullable();
            $table->string('image_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('publish_title')->nullable();
            $table->text('publish_description')->nullable();
            $table->json('hashtags')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('content_pillar');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_ideas');
    }
};
