# Klaus vom Amt Video Factory

Single-admin Laravel + Filament dashboard to automate sarcastic short-form comedy videos for **Klaus vom Amt**.

Entertainment and satire only — not legal advice.

## Herd setup

The project lives at `~/PersonalProjects/klaus-vom-amt-video-factory`. Link it once:

```bash
cd ~/PersonalProjects/klaus-vom-amt-video-factory
herd link klaus-vom-amt-video-factory
```

Herd serves the app at:

**http://klaus-vom-amt-video-factory.test**

Admin panel: **http://klaus-vom-amt-video-factory.test/admin**

No `php artisan serve` needed — Herd handles HTTP and TLS.

## First-time setup

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

Default admin: `admin@klaus-vom-amt.local` / `klaus-admin-change-me`

## Background workers (required for video jobs)

Herd does not run queues automatically. In a second terminal:

```bash
composer dev
```

That runs the **queue worker**, **scheduler**, and **Pail** logs together.

Or run them separately:

```bash
php artisan queue:work
php artisan schedule:work   # optional if Herd scheduler is enabled
php artisan pail
```

> Herd can run `schedule:run` every minute for parked sites (Settings → General → Scheduler). If enabled, you only need the queue worker.

## Instagram / public videos

`APP_PUBLIC_VIDEO_BASE_URL` should match your Herd URL so Instagram can fetch reels:

```
APP_PUBLIC_VIDEO_BASE_URL=http://klaus-vom-amt-video-factory.test
```

Videos are exposed at `/klaus-media/{path}`.

## Scheduler

Auto-publish at **08:00**, **14:00**, and **20:00** via `klaus:auto-publish`.

## External tools

- `ffmpeg` — set `FFMPEG_PATH` (Herd sites use system PHP; FFmpeg is usually `/opt/homebrew/bin/ffmpeg` on Apple Silicon)
- `edge-tts` — `pip install edge-tts`, then set `EDGE_TTS_PATH=edge-tts`

## Env

See `.env.example` for OpenAI, YouTube, Instagram, and TikTok credentials.
