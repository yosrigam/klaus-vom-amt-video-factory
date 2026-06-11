<?php

return [
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'edge_tts_path' => env('EDGE_TTS_PATH', 'edge-tts'),
    'edge_tts_voice' => env('EDGE_TTS_VOICE', 'de-DE-ConradNeural'),
    'public_video_base_url' => env('APP_PUBLIC_VIDEO_BASE_URL'),
    'video_width' => 1080,
    'video_height' => 1920,
    'disclaimer' => 'Entertainment and satire only. Not legal advice.',
];
