<?php

namespace App\Support;

class BulkVideoIdeaFormat
{
    public static function template(): string
    {
        return <<<'TEXT'
Wrap each idea in double asterisks (** ... **). Use Title, Hook, and Short concept labels:

**Title: Sunday Mowing Tribunal
Hook: Your lawn is a crime scene
Short concept: Klaus reviews neighbor complaints about Sunday trimming.**

**Title: Pfand Initiation
Hook: Welcome to Germany
Short concept: Klaus explains the sacred bottle deposit ritual across three painful steps.**
TEXT;
    }

    public static function example(): string
    {
        return <<<'TEXT'
**Title: Sunday Mowing Tribunal
Hook: Your lawn is a crime scene
Short concept: Klaus reviews neighbor complaints.**

**Title: Pfand Initiation
Hook: Welcome to Germany
Short concept: Klaus explains the bottle deposit ritual.**
TEXT;
    }
}
