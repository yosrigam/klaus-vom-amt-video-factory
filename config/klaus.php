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
    'background_music_paths' => [
        resource_path('audio/comedy-cartoon-1.mp3'),
        resource_path('audio/comedy-cartoon-2.mp3'),
        resource_path('audio/comedy-cartoon-3.mp3'),
        resource_path('audio/comedy-cartoon-4.mp3'),
    ],
    'background_music_path' => env('KLAUS_BACKGROUND_MUSIC_PATH'),
    'background_music_volume' => env('KLAUS_BACKGROUND_MUSIC_VOLUME', 0.20),
    'narration_volume' => env('KLAUS_NARRATION_VOLUME', 1.10),
    'script_german_intro' => env('KLAUS_SCRIPT_GERMAN_INTRO', 'Klaus vom Amt hier.'),
    'script_german_outro' => env('KLAUS_SCRIPT_GERMAN_OUTRO', "Der Vorgang ist abgeschlossen.\n\nAuf Wiedersehen."),
    'narration_style' => env('KLAUS_NARRATION_STYLE', 'awkward'),
    'narration_styles' => [
        'default' => [
            'rate' => '-4%',
            'pitch' => env('EDGE_TTS_PITCH', '-3Hz'),
            'volume' => env('EDGE_TTS_VOLUME', '+0%'),
            'sentence_pause_seconds' => env('EDGE_TTS_SENTENCE_PAUSE', 0.50),
            'short_phrase_pause_seconds' => env('EDGE_TTS_SHORT_PHRASE_PAUSE', 0.35),
            'bookend_pause_seconds' => env('EDGE_TTS_BOOKEND_PAUSE', 1.0),
            'line_break_pause_seconds' => env('EDGE_TTS_LINE_BREAK_PAUSE', 0.75),
            'dramatic_pause_multiplier' => 1.0,
            'merge_short_phrases' => env('EDGE_TTS_MERGE_SHORT_PHRASES', false),
            'preserve_line_breaks' => true,
            'apply_formatter' => false,
        ],
        'awkward' => [
            'rate' => '-6%',
            'pitch' => env('EDGE_TTS_PITCH', '-3Hz'),
            'volume' => env('EDGE_TTS_VOLUME', '+0%'),
            'sentence_pause_seconds' => env('EDGE_TTS_SENTENCE_PAUSE', 0.50),
            'short_phrase_pause_seconds' => env('EDGE_TTS_SHORT_PHRASE_PAUSE', 0.35),
            'bookend_pause_seconds' => env('EDGE_TTS_BOOKEND_PAUSE', 1.0),
            'line_break_pause_seconds' => env('EDGE_TTS_LINE_BREAK_PAUSE', 0.75),
            'dramatic_pause_multiplier' => env('EDGE_TTS_DRAMATIC_PAUSE_MULTIPLIER', 1.0),
            'merge_short_phrases' => false,
            'preserve_line_breaks' => true,
            'apply_formatter' => true,
        ],
    ],
    'voice_profile' => [
        'voice' => env('EDGE_TTS_VOICE', 'en-GB-ThomasNeural'),
        'intro_voice' => env('EDGE_TTS_INTRO_VOICE', 'de-DE-ConradNeural'),
        'base_rate' => env('EDGE_TTS_RATE', '+2%'),
        'base_pitch' => env('EDGE_TTS_PITCH', '-3Hz'),
        'base_volume' => env('EDGE_TTS_VOLUME', '+0%'),
        'merge_short_phrases' => env('EDGE_TTS_MERGE_SHORT_PHRASES', false),
        'sentence_pause' => env('EDGE_TTS_SENTENCE_PAUSE', 0.08),
        'short_phrase_pause' => env('EDGE_TTS_SHORT_PHRASE_PAUSE', 0.06),
        'bookend_pause' => env('EDGE_TTS_BOOKEND_PAUSE', 0.18),
        'pre_punchline_pause' => env('EDGE_TTS_PRE_PUNCHLINE_PAUSE', 0.10),
        'punchline_pause' => env('EDGE_TTS_PUNCHLINE_PAUSE', 0.22),
        'paragraph_break_pause' => env('EDGE_TTS_PARAGRAPH_BREAK_PAUSE', 0.18),
        'line_break_pause' => env('EDGE_TTS_LINE_BREAK_PAUSE', 0.0),
        'delivery_styles' => [
            'NeutralObservation' => [
                'rate' => '+2%',
                'pitch' => '-2Hz',
                'volume' => '+0%',
                'pause_after' => 0.08,
            ],
            'MildConcern' => [
                'rate' => '+0%',
                'pitch' => '-1Hz',
                'volume' => '+0%',
                'pause_after' => 0.10,
            ],
            'Disappointed' => [
                'rate' => '-2%',
                'pitch' => '-4Hz',
                'volume' => '-1%',
                'pause_after' => 0.12,
            ],
            'Punchline' => [
                'rate' => '-4%',
                'pitch' => '-5Hz',
                'volume' => '+1%',
                'pause_after' => 0.22,
            ],
            'BureaucraticClosure' => [
                'rate' => '-6%',
                'pitch' => '-4Hz',
                'volume' => '+0%',
                'pause_after' => 0.26,
            ],
        ],
    ],
    'beat_markers' => [
        'pause' => (float) env('EDGE_TTS_BEAT_PAUSE', 1.0),
        'beat' => (float) env('EDGE_TTS_BEAT_BEAT', 1.40),
        'long_beat' => (float) env('EDGE_TTS_BEAT_LONG_BEAT', 2.0),
    ],
    'edge_tts_pause_multiplier' => (float) env('EDGE_TTS_PAUSE_MULTIPLIER', 1.0),
    'edge_tts_trim_trailing_silence' => env('EDGE_TTS_TRIM_TRAILING_SILENCE', false),
    'edge_tts_trim_silence' => [
        'stop_threshold_db' => (int) env('EDGE_TTS_TRIM_SILENCE_THRESHOLD_DB', -55),
        'stop_duration_seconds' => (float) env('EDGE_TTS_TRIM_SILENCE_DURATION', 0.12),
    ],
    'tts_driver' => env('KLAUS_TTS_DRIVER', 'edge'),
    'edge_tts_path' => env('EDGE_TTS_PATH', 'edge-tts'),
    'edge_tts_voice' => env('EDGE_TTS_VOICE'),
    'edge_tts_voice_variant' => env('EDGE_TTS_VOICE_VARIANT', 'a'),
    'edge_tts_voice_a' => env('EDGE_TTS_VOICE_A', 'en-US-GuyNeural'),
    'edge_tts_voice_b' => env('EDGE_TTS_VOICE_B', 'en-GB-ThomasNeural'),
    'edge_tts_voices' => [
        'a' => env('EDGE_TTS_VOICE_A', 'en-US-GuyNeural'),
        'b' => env('EDGE_TTS_VOICE_B', 'en-GB-ThomasNeural'),
    ],
    'edge_tts_intro_voice' => env('EDGE_TTS_INTRO_VOICE', 'de-DE-ConradNeural'),
    'edge_tts_intro_phrase_count' => env('EDGE_TTS_INTRO_PHRASE_COUNT', 1),
    'edge_tts_chunk_sentences' => env('EDGE_TTS_CHUNK_SENTENCES', true),
    'edge_tts_merge_short_phrases' => env('EDGE_TTS_MERGE_SHORT_PHRASES', false),
    'edge_tts_sentence_pause_seconds' => env('EDGE_TTS_SENTENCE_PAUSE', 0.30),
    'edge_tts_short_phrase_pause_seconds' => env('EDGE_TTS_SHORT_PHRASE_PAUSE', 0.18),
    'edge_tts_bookend_pause_seconds' => env('EDGE_TTS_BOOKEND_PAUSE', 0.70),
    'edge_tts_rate' => env('EDGE_TTS_RATE', '+0%'),
    'edge_tts_pitch' => env('EDGE_TTS_PITCH', '-3Hz'),
    'edge_tts_volume' => env('EDGE_TTS_VOLUME', '+0%'),
    'edge_tts_loudnorm_enabled' => env('EDGE_TTS_LOUDNORM_ENABLED', true),
    'edge_tts_loudnorm' => [
        'I' => -16,
        'TP' => -1.5,
        'LRA' => 8,
    ],
    'public_video_base_url' => env('APP_PUBLIC_VIDEO_BASE_URL'),
    'video_width' => 1080,
    'video_height' => 1920,
    'captions' => [
        'font_path' => resource_path('fonts/Anton-Regular.ttf'),
        'font_size' => 44,
        'min_font_size' => 22,
        'line_gap' => 10,
        'max_band_height' => 360,
        'band_height' => 200,
        'horizontal_margin' => 48,
        'max_phrase_chars' => 36,
        'max_visible_words' => 3,
        'word_gap' => 28,
        'word_gap_seconds' => 0.12,
        'min_word_seconds' => 0.40,
        'seconds_per_char' => 0.050,
        'comma_pause_seconds' => 0.35,
        'sentence_pause_seconds' => 0.65,
        'seconds_per_word' => 0.42,
        'pill_padding_x' => 28,
        'pill_padding_y' => 22,
        // Nudge text down inside the pill for all-caps fonts (Anton) whose glyphs
        // sit optically high despite correct bbox centering. Positive = downward.
        'optical_offset_y' => env('KLAUS_CAPTION_OPTICAL_OFFSET_Y', 3),
        'outline_width' => 0,
        'margin_bottom' => 320,
        'pill_radius' => 20,
        'pill_background_color' => [255, 255, 255],
        'pill_background_alpha' => 52,
        'pill_border_width' => 3,
        'pill_border_color' => [0, 0, 0],
        'text_color' => [0, 0, 0],
    ],
    'disclaimer' => 'Entertainment and satire only. Not legal advice.',
    'image_style_lock' => <<<'LOCK'
    Generate this scene-specific image.
CHARACTER BIBLE — KLAUS VOM AMT V1

Character name:
Klaus vom Amt

Identity:
Recurring fictional German bureaucrat and administrative office worker.

Age:
53 years old.

Gender:
Male.

Nationality:
German.

Face:

Middle-aged German man.
Short dark blond hair.
Slightly receding hairline.
Clean-shaven.
Square face.
Light skin tone.
Average build.

Facial expression:

Neutral.
Mild disappointment.
Slight skepticism.
One eyebrow occasionally raised.
Looks permanently tired of humanity.
Never smiling.
Never excited.
Never angry.

Personality reflected visually:

Passive-aggressive.
Deadpan.
Sarcastic.
Emotionally exhausted.
Technically correct.
Looks like he has reviewed paperwork for 30 years.

Clothing:

Beige office jacket.
White shirt.
Dark tie.
Dark trousers.
Practical black shoes.

Accessories:

Clipboard.
Official-looking documents.
Coffee mug occasionally.

Visual consistency requirements:

Face must remain identical.
Hairline must remain identical.
Age must remain identical.
Clothing colors must remain identical.
Body proportions must remain identical.

Klaus must always be immediately recognizable.

STYLE GUIDE

Modern viral social-media illustration.

Clean vector-cartoon aesthetic.

Semi-realistic face.

Bold thick black outlines.

Bright but controlled colors.

High-contrast cel shading.

Flat pastel backgrounds.

Strong silhouette.

Character centered.

Minimal environmental details.

Large negative space.

No realism.

No photographic textures.

No skin pores.

No painterly effects.

No cinematic photography.

No clutter.

No excessive detail.

Professional digital illustration.

TikTok / Instagram Reels / YouTube Shorts style.

Ultra sharp.

Highly recognizable recurring mascot.

VISUAL MOOD

A government employee who has processed forms for thirty years and no longer believes approval is possible.
LOCK,
];
