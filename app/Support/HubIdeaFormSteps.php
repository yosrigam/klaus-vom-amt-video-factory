<?php

namespace App\Support;

use App\Models\HubIdea;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class HubIdeaFormSteps
{
    public static function ideaComplete(Get $get, ?HubIdea $record): bool
    {
        return filled(static::value($get, 'idea_text', $record));
    }

    public static function contentComplete(Get $get, ?HubIdea $record): bool
    {
        return static::ideaComplete($get, $record)
            && filled(static::value($get, 'script', $record))
            && filled(static::value($get, 'image_prompt', $record));
    }

    public static function imageComplete(Get $get, ?HubIdea $record): bool
    {
        return filled(static::value($get, 'image_path', $record));
    }

    public static function productionUnlocked(Get $get, ?HubIdea $record): bool
    {
        return static::contentComplete($get, $record) && static::imageComplete($get, $record);
    }

    public static function progressHtml(Get $get, ?HubIdea $record): HtmlString
    {
        $steps = [
            ['Idea', static::ideaComplete($get, $record)],
            ['Script & image prompt', static::contentComplete($get, $record)],
            ['Image', static::imageComplete($get, $record)],
            ['Production', static::productionUnlocked($get, $record)],
        ];

        $items = collect($steps)->map(function (array $step, int $index): string {
            [$label, $done] = $step;
            $number = $index + 1;
            $marker = $done ? '✓' : '○';
            $tone = $done ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500';

            return '<li class="'.$tone.'">'.$marker.' Step '.$number.': '.e($label).'</li>';
        })->implode('');

        return new HtmlString(
            '<ol class="list-none space-y-1 text-sm m-0 p-0">'.$items.'</ol>'
        );
    }

    public static function lockedMessage(string $previousStepLabel): HtmlString
    {
        return new HtmlString(
            '<p class="text-sm text-gray-500 dark:text-gray-400 italic">Complete <strong>'
            .e($previousStepLabel)
            .'</strong> first to unlock this step.</p>'
        );
    }

    private static function value(Get $get, string $field, ?HubIdea $record): mixed
    {
        $value = $get($field);

        if ($field === 'image_path' && is_array($value)) {
            $value = collect($value)
                ->flatten()
                ->first(fn (mixed $item): bool => is_string($item) && str_contains($item, '/'));
        }

        if (filled($value)) {
            return $value;
        }

        return $record?->{$field};
    }
}
