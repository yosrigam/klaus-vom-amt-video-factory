@props([
    'title' => null,
    'description' => null,
    'url' => null,
])

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-klaus.seo :title="$title" :description="$description" :url="$url" />

    @fonts
    @vite(['resources/css/klaus-landing.css', 'resources/js/klaus-landing.js'])
</head>
<body x-data="klausSite" class="min-h-screen">
    {{ $slot }}

    <x-klaus.modal />
    <x-klaus.toasts />
    <x-klaus.chat-widget />
</body>
</html>
