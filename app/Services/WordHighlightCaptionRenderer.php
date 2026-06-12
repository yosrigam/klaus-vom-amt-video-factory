<?php

namespace App\Services;

use RuntimeException;

class WordHighlightCaptionRenderer
{
    /**
     * @return array{width: int, height: int}
     */
    public function renderPhrase(string $phrase, string $absoluteOutputPath): array
    {
        $words = preg_split('/\s+/u', trim($phrase)) ?: [];

        $words = array_values(array_filter(
            array_map(fn (string $word) => mb_strtoupper(trim($word), 'UTF-8'), $words),
            fn (string $word) => $word !== '',
        ));

        if ($words === []) {
            throw new RuntimeException('Cannot render captions for an empty phrase.');
        }

        $fontPath = config('klaus.captions.font_path');
        if (! is_readable($fontPath)) {
            throw new RuntimeException("Caption font not found: {$fontPath}");
        }

        $width = (int) config('klaus.video_width', 1080);
        $maxBandHeight = (int) config('klaus.captions.max_band_height', 360);
        $horizontalMargin = (int) config('klaus.captions.horizontal_margin', 48);
        $maxTextWidth = $width - ($horizontalMargin * 2) - ((int) config('klaus.captions.pill_padding_x', 24) * 2);
        $fontSize = (int) config('klaus.captions.font_size', 44);
        $minFontSize = (int) config('klaus.captions.min_font_size', 22);
        $lineGap = (int) config('klaus.captions.line_gap', 10);
        $pillPaddingX = (int) config('klaus.captions.pill_padding_x', 24);
        $pillPaddingY = (int) config('klaus.captions.pill_padding_y', 16);
        $pillRadius = (int) config('klaus.captions.pill_radius', 20);
        $textColor = config('klaus.captions.text_color', [0, 0, 0]);
        $layout = null;

        while ($fontSize >= $minFontSize) {
            $candidate = $this->layoutPhraseLines($words, $fontPath, $fontSize, $maxTextWidth, $lineGap, $pillPaddingY);

            if ($candidate !== null && $candidate['band_height'] <= $maxBandHeight) {
                $layout = $candidate;

                break;
            }

            $fontSize -= 2;
        }

        if ($layout === null) {
            throw new RuntimeException('Caption phrase exceeds safe layout even at minimum font size.');
        }

        $bandHeight = $layout['band_height'];
        $contentWidth = $layout['content_width'];
        $startX = $horizontalMargin + (int) round(($width - ($horizontalMargin * 2) - $contentWidth) / 2);

        $image = imagecreatetruecolor($width, $bandHeight);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagealphablending($image, true);

        $this->drawPill(
            $image,
            $startX - $pillPaddingX,
            $layout['pill_top'],
            $startX + $contentWidth + $pillPaddingX,
            $layout['pill_bottom'],
            $pillRadius,
        );

        foreach ($layout['lines'] as $line) {
            $lineX = $startX + (int) round(($contentWidth - $line['width']) / 2);
            $this->drawText($image, $fontSize, $lineX, $line['baseline_y'], $line['text'], $fontPath, $textColor);
        }

        if (! imagepng($image, $absoluteOutputPath)) {
            throw new RuntimeException("Failed to write caption image: {$absoluteOutputPath}");
        }

        return ['width' => $width, 'height' => $bandHeight];
    }

    /**
     * @param  array<int, string>  $words
     * @return array{
     *     band_height: int,
     *     content_width: int,
     *     pill_top: int,
     *     pill_bottom: int,
     *     lines: array<int, array{text: string, width: int, baseline_y: int}>
     * }|null
     */
    protected function layoutPhraseLines(
        array $words,
        string $fontPath,
        int $fontSize,
        int $maxTextWidth,
        int $lineGap,
        int $pillPaddingY,
    ): ?array {
        $wrapped = $this->wrapWordsIntoLines($words, $fontPath, $fontSize, $maxTextWidth);

        if ($wrapped === []) {
            return null;
        }

        $borderWidth = (int) config('klaus.captions.pill_border_width', 3);
        $opticalOffsetY = (int) config('klaus.captions.optical_offset_y', 3);

        $lineMetrics = [];
        $contentWidth = 0;
        $blockHeight = 0;

        foreach ($wrapped as $index => $line) {
            $bounds = $this->measureTextBounds($line['text'], $fontPath, $fontSize);

            if ($index > 0) {
                $blockHeight += $lineGap;
            }

            $blockHeight += $bounds['text_height'];
            $lineMetrics[] = [
                'text' => $line['text'],
                'width' => $bounds['width'],
                'text_top' => $bounds['text_top'],
                'text_bottom' => $bounds['text_bottom'],
                'text_height' => $bounds['text_height'],
            ];
            $contentWidth = max($contentWidth, $bounds['width']);
        }

        $pillTop = $borderWidth;
        $pillHeight = $blockHeight + (2 * $pillPaddingY) + (2 * $borderWidth) + $opticalOffsetY;
        $pillBottom = $pillTop + $pillHeight;
        $bandHeight = $pillBottom + $borderWidth;

        $innerTop = $pillTop + $pillPaddingY + $borderWidth;
        $textBlockTop = $innerTop + $opticalOffsetY;

        $lines = [];

        foreach ($lineMetrics as $index => $metric) {
            if ($index === 0) {
                $baselineY = (int) round($textBlockTop - $metric['text_top']);
            } else {
                $previous = $lineMetrics[$index - 1];
                $previousBaseline = $lines[$index - 1]['baseline_y'];
                $lineTop = $previousBaseline + $previous['text_bottom'] + $lineGap;
                $baselineY = (int) round($lineTop - $metric['text_top']);
            }

            $lines[] = [
                'text' => $metric['text'],
                'width' => $metric['width'],
                'baseline_y' => $baselineY,
            ];
        }

        return [
            'band_height' => $bandHeight,
            'content_width' => $contentWidth,
            'pill_top' => $pillTop,
            'pill_bottom' => $pillBottom,
            'lines' => $lines,
        ];
    }

    /**
     * @return array{
     *     width: int,
     *     text_top: int,
     *     text_bottom: int,
     *     text_height: int,
     *     ascent: int,
     *     descent: int
     * }
     */
    protected function measureTextBounds(string $text, string $fontPath, int $fontSize): array
    {
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);

        if ($box === false) {
            throw new RuntimeException('Failed to measure caption text bounds.');
        }

        $textTop = (int) round(min($box[1], $box[3], $box[5], $box[7]));
        $textBottom = (int) round(max($box[1], $box[3], $box[5], $box[7]));

        return [
            'width' => (int) round(abs($box[2] - $box[0])),
            'text_top' => $textTop,
            'text_bottom' => $textBottom,
            'text_height' => $textBottom - $textTop,
            'ascent' => -$textTop,
            'descent' => $textBottom,
        ];
    }

    /**
     * @param  array<int, string>  $words
     * @return array<int, array{text: string, width: int, height: int}>
     */
    protected function wrapWordsIntoLines(array $words, string $fontPath, int $fontSize, int $maxWidth): array
    {
        $lines = [];
        $currentWords = [];

        foreach ($words as $word) {
            $candidateWords = array_merge($currentWords, [$word]);
            $candidateText = implode(' ', $candidateWords);
            $box = imagettfbbox($fontSize, 0, $fontPath, $candidateText);
            $candidateWidth = (int) abs($box[2] - $box[0]);

            if ($candidateWidth <= $maxWidth || $currentWords === []) {
                $currentWords = $candidateWords;

                continue;
            }

            $lineText = implode(' ', $currentWords);
            $lineBounds = $this->measureTextBounds($lineText, $fontPath, $fontSize);
            $lines[] = [
                'text' => $lineText,
                'width' => $lineBounds['width'],
                'height' => $lineBounds['text_height'],
            ];
            $currentWords = [$word];
        }

        if ($currentWords !== []) {
            $lineText = implode(' ', $currentWords);
            $lineBounds = $this->measureTextBounds($lineText, $fontPath, $fontSize);

            if ($lineBounds['width'] > $maxWidth) {
                return [];
            }

            $lines[] = [
                'text' => $lineText,
                'width' => $lineBounds['width'],
                'height' => $lineBounds['text_height'],
            ];
        }

        return $lines;
    }

    /**
     * @param  array<int, string>  $words
     * @return array{width: int, height: int}
     */
    public function renderWords(array $words, int $highlightIndex, string $absoluteOutputPath): array
    {
        if (! extension_loaded('gd') || ! function_exists('imagettftext')) {
            throw new RuntimeException('PHP GD with FreeType support is required for word-highlight captions.');
        }

        $fontPath = config('klaus.captions.font_path');
        if (! is_readable($fontPath)) {
            throw new RuntimeException("Caption font not found: {$fontPath}");
        }

        $words = array_values(array_filter(
            array_map(fn (string $word) => mb_strtoupper(trim($word), 'UTF-8'), $words),
            fn (string $word) => $word !== '',
        ));

        if ($words === []) {
            throw new RuntimeException('Cannot render captions for an empty line.');
        }

        $width = (int) config('klaus.video_width', 1080);
        $horizontalMargin = (int) config('klaus.captions.horizontal_margin', 48);
        $maxWidth = $width - ($horizontalMargin * 2);
        $fontSize = (int) config('klaus.captions.font_size', 52);
        $minFontSize = (int) config('klaus.captions.min_font_size', 36);
        $wordGap = (int) config('klaus.captions.word_gap', 28);
        $pillPaddingX = (int) config('klaus.captions.pill_padding_x', 24);
        $pillPaddingY = (int) config('klaus.captions.pill_padding_y', 14);
        $pillRadius = (int) config('klaus.captions.pill_radius', 20);
        $borderWidth = (int) config('klaus.captions.pill_border_width', 3);
        $opticalOffsetY = (int) config('klaus.captions.optical_offset_y', 3);

        $layouts = [];
        while ($fontSize >= $minFontSize) {
            $layouts = $this->measureWords($words, $fontPath, $fontSize, $wordGap);
            $contentWidth = end($layouts)['total_width'];

            if ($contentWidth + ($pillPaddingX * 2) <= $maxWidth) {
                break;
            }

            $fontSize -= 4;
        }

        $contentWidth = end($layouts)['total_width'] ?? 0;

        if ($layouts === [] || $contentWidth + ($pillPaddingX * 2) > $maxWidth) {
            throw new RuntimeException('Caption line exceeds safe width even at minimum font size.');
        }

        $textTop = min(array_column($layouts, 'text_top'));
        $textBottom = max(array_column($layouts, 'text_bottom'));
        $textHeight = $textBottom - $textTop;

        $pillTop = $borderWidth;
        $pillHeight = $textHeight + (2 * $pillPaddingY) + (2 * $borderWidth) + $opticalOffsetY;
        $pillBottom = $pillTop + $pillHeight;
        $bandHeight = $pillBottom + $borderWidth;

        $image = imagecreatetruecolor($width, $bandHeight);
        imagesavealpha($image, true);
        imagealphablending($image, false);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagealphablending($image, true);

        $startX = $horizontalMargin + (int) round(($maxWidth - $contentWidth) / 2);
        $innerTop = $pillTop + $pillPaddingY + $borderWidth;
        $baselineY = (int) round($innerTop + $opticalOffsetY - $textTop);

        $this->drawPill(
            $image,
            $startX - $pillPaddingX,
            $pillTop,
            $startX + $contentWidth + $pillPaddingX,
            $pillBottom,
            $pillRadius,
        );

        $textColor = config('klaus.captions.text_color', [255, 255, 255]);
        $outlineWidth = (int) config('klaus.captions.outline_width', 0);

        foreach ($layouts as $index => $layout) {
            $this->drawText(
                $image,
                $fontSize,
                $startX + $layout['text_x'],
                $baselineY,
                $words[$index],
                $fontPath,
                $textColor,
                $outlineWidth,
            );
        }

        if (! imagepng($image, $absoluteOutputPath)) {
            throw new RuntimeException("Failed to write caption image: {$absoluteOutputPath}");
        }

        return ['width' => $width, 'height' => $bandHeight];
    }

    /**
     * @param  array<int, string>  $words
     * @return array<int, array{
     *     text_x: int,
     *     width: int,
     *     text_top: int,
     *     text_bottom: int,
     *     text_height: int,
     *     total_width: int
     * }>
     */
    protected function measureWords(
        array $words,
        string $fontPath,
        int $fontSize,
        int $wordGap,
    ): array {
        $layouts = [];
        $cursor = 0;
        $lastIndex = count($words) - 1;

        foreach ($words as $index => $word) {
            $bounds = $this->measureTextBounds($word, $fontPath, $fontSize);

            $layouts[] = [
                'text_x' => $cursor,
                'width' => $bounds['width'],
                'text_top' => $bounds['text_top'],
                'text_bottom' => $bounds['text_bottom'],
                'text_height' => $bounds['text_height'],
                'total_width' => $cursor + $bounds['width'] + ($index < $lastIndex ? $wordGap : 0),
            ];

            $cursor += $bounds['width'] + ($index < $lastIndex ? $wordGap : 0);
        }

        return $layouts;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    protected function drawText(
        \GdImage $image,
        int $fontSize,
        int $x,
        int $baselineY,
        string $text,
        string $fontPath,
        array $rgb,
        int $outlineWidth = 0,
    ): void {
        $fill = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

        if ($outlineWidth > 0) {
            $outline = imagecolorallocate($image, 0, 0, 0);

            for ($ox = -$outlineWidth; $ox <= $outlineWidth; $ox++) {
                for ($oy = -$outlineWidth; $oy <= $outlineWidth; $oy++) {
                    if ($ox === 0 && $oy === 0) {
                        continue;
                    }

                    imagettftext($image, $fontSize, 0, $x + $ox, $baselineY + $oy, $outline, $fontPath, $text);
                }
            }
        }

        imagettftext($image, $fontSize, 0, $x, $baselineY, $fill, $fontPath, $text);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    protected function allocateColor(\GdImage $image, array $rgb, ?int $alpha = null): int
    {
        if ($alpha !== null) {
            return imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], max(0, min(127, $alpha)));
        }

        return imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }

    protected function drawPill(
        \GdImage $image,
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        int $radius,
    ): void {
        $borderWidth = max(0, (int) config('klaus.captions.pill_border_width', 3));
        $fillColor = $this->allocateColor(
            $image,
            config('klaus.captions.pill_background_color', [255, 255, 255]),
            (int) config('klaus.captions.pill_background_alpha', 52),
        );

        $this->drawFilledRoundRect($image, $x1, $y1, $x2, $y2, $radius, $fillColor);

        if ($borderWidth > 0) {
            $borderColor = $this->allocateColor(
                $image,
                config('klaus.captions.pill_border_color', [0, 0, 0]),
            );

            $this->drawStrokedRoundRect(
                $image,
                $x1,
                $y1,
                $x2,
                $y2,
                $radius,
                $borderColor,
                $borderWidth,
            );
        }
    }

    protected function drawStrokedRoundRect(
        \GdImage $image,
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        int $radius,
        int $color,
        int $thickness,
    ): void {
        if ($x2 <= $x1 || $y2 <= $y1) {
            return;
        }

        $radius = max(0, min($radius, (int) (($x2 - $x1) / 2), (int) (($y2 - $y1) / 2)));
        $thickness = max(1, $thickness);
        imagesetthickness($image, $thickness);

        $half = (int) max(1, floor($thickness / 2));
        $x1 += $half;
        $y1 += $half;
        $x2 -= $half;
        $y2 -= $half;

        imageline($image, $x1 + $radius, $y1, $x2 - $radius, $y1, $color);
        imageline($image, $x1 + $radius, $y2, $x2 - $radius, $y2, $color);
        imageline($image, $x1, $y1 + $radius, $x1, $y2 - $radius, $color);
        imageline($image, $x2, $y1 + $radius, $x2, $y2 - $radius, $color);

        if ($radius > 0) {
            imagearc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color);
            imagearc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color);
            imagearc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color);
            imagearc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color);
        }

        imagesetthickness($image, 1);
    }

    protected function drawFilledRoundRect(
        \GdImage $image,
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        int $radius,
        int $color,
    ): void {
        if ($x2 <= $x1 || $y2 <= $y1) {
            return;
        }

        $radius = max(0, min($radius, (int) (($x2 - $x1) / 2), (int) (($y2 - $y1) / 2)));

        if ($radius === 0) {
            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $color);

            return;
        }

        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
