<?php

namespace App\Contracts;

interface PublishableVideo
{
    public function publishVideoPath(): ?string;

    public function publishTitle(): string;

    public function publishDescription(): string;

    /**
     * @return array<int, string>
     */
    public function publishHashtags(): array;
}
