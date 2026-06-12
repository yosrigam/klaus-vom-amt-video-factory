<?php

return [
    'local_admin' => [
        'email' => env('LOCAL_ADMIN_EMAIL', 'admin@klaus-vom-amt.local'),
        'password' => env('LOCAL_ADMIN_PASSWORD', 'klaus-admin-change-me'),
    ],

    'seo' => [
        'site_name' => 'Klaus vom Amt',
        'title' => 'Klaus vom Amt — Your happiness is currently under administrative review.',
        'description' => 'Satirical German bureaucracy from Klaus vom Amt. Submit yourself for evaluation, appeal previous decisions, and explore fake forms. Entertainment only — not legal advice.',
        'keywords' => 'Klaus vom Amt, German bureaucracy, Bürokratie, satire, comedy, Sachbearbeiter, administrative humor',
        'image' => 'images/klaus-banner.png',
        'locale' => 'de_DE',
        'twitter_site' => null,
    ],

    'social' => [
        'instagram' => 'https://www.instagram.com/klaus.vom.amt/',
        'youtube' => 'https://www.youtube.com/@Klaus.vomamt',
        'tiktok' => 'https://www.tiktok.com/@klaus.vom.amt',
    ],

    'legal' => [
        'contact_email' => env('KLAUS_CONTACT_EMAIL', 'hello@klausvomamt.com'),
        'terms_url' => env('KLAUS_TERMS_URL'),
        'privacy_url' => env('KLAUS_PRIVACY_URL'),
    ],
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'edge_tts_path' => env('EDGE_TTS_PATH', 'edge-tts'),
    'edge_tts_voice' => env('EDGE_TTS_VOICE', 'de-DE-ConradNeural'),
    'public_video_base_url' => env('APP_PUBLIC_VIDEO_BASE_URL'),
    'video_width' => 1080,
    'video_height' => 1920,
    'disclaimer' => 'Entertainment and satire only. Not legal advice.',
    'character_lock' => 'Klaus vom Amt. Male. 53 years old. German. Long rectangular face. Strong jawline. Pronounced forehead. Visible forehead wrinkles. Mild under-eye bags. Short dark-blond hair. Slightly receding hairline. Grey-blue eyes. Rectangular black office glasses. Clean-shaven. Average middle-aged office-worker physique. 178 cm tall. Neutral expression. Deadpan expression. Mild disappointment. One eyebrow slightly raised. Never smiling. Beige office jacket. White button-up shirt. Dark navy tie. Dark charcoal trousers. Black practical shoes. The face, hairstyle, glasses, age, body proportions and clothing colors must remain identical across all images. Always recognizable as Klaus vom Amt.',
    'visual_style' => <<<'STYLE'
Modern viral social-media illustration.
Bold thick black outlines.
Clean vector-cartoon aesthetic.
Semi-realistic facial features.
Slight caricature proportions.
Bright saturated colors.
High-contrast cel shading.
Flat pastel background.
Minimal environmental details.
Large negative space for captions.
Character centered.
Strong silhouette.
No photographic textures.
No painterly effects.
No realism.
No clutter.
Ultra sharp.
TikTok/Reels/Shorts optimized.
STYLE,
];
