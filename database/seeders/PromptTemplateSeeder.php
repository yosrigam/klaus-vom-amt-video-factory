<?php

namespace Database\Seeders;

use App\Enums\WorkflowStep;
use App\Models\PromptTemplate;
use Illuminate\Database\Seeder;

class PromptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Klaus Idea Batch (10)',
                'workflow_step' => WorkflowStep::IdeaGeneration,
                'prompt' => <<<'PROMPT'
Generate exactly 10 unique short-form video ideas for Klaus vom Amt.

Content pillar: {{pillar_name}}
Description: {{pillar_description}}
Examples: {{pillar_examples}}
Klaus angle: {{klaus_angle}}

Tone: sarcastic, passive-aggressive, dark humor, deadpan, bureaucratic, mildly disappointed.
Entertainment and satire only. Not legal advice. {{disclaimer}}

Return valid JSON only:
{
  "ideas": [
    {
      "title": "...",
      "hook": "...",
      "short_concept": "..."
    }
  ]
}
PROMPT,
            ],
            [
                'name' => 'Klaus Script (25-40s)',
                'workflow_step' => WorkflowStep::ScriptGeneration,
                'prompt' => <<<'PROMPT'
Write a 25-40 second Klaus vom Amt script.

Title: {{title}}
Hook: {{hook}}
Concept: {{short_concept}}
Pillar: {{pillar_name}}
Klaus angle: {{klaus_angle}}

Open like: "Klaus vom Amt here. Unfortunately, Germany has reviewed your behavior."
Tone: sarcastic, passive-aggressive, dark humor, deadpan, bureaucratic, mildly disappointed.
Entertainment only. {{disclaimer}}

Return valid JSON only:
{
  "script": "...",
  "voice_text": "...",
  "publish_title": "...",
  "publish_description": "...",
  "hashtags": ["KlausVomAmt", "Germany"]
}
PROMPT,
            ],
            [
                'name' => 'Klaus Image Prompt (9:16)',
                'workflow_step' => WorkflowStep::ImagePromptGeneration,
                'prompt' => <<<'PROMPT'
Create a vertical 9:16 image prompt for Klaus vom Amt.

Video idea:
{{idea}}

Script:
{{script}}

Character:
Klaus vom Amt.
German bureaucrat.
52 years old.
Short dark-blond hair.
Slightly receding hairline.
Clean-shaven.
Neutral expression.
Mild disappointment.
Looks permanently tired of humanity.
Beige government office jacket.
White shirt.
Dark trousers.
Practical black shoes.
Holding clipboard.

Style:
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

Return valid JSON only:
{
  "image_prompt": "..."
}
PROMPT,
            ],
            [
                'name' => 'Klaus Captions SRT',
                'workflow_step' => WorkflowStep::CaptionGeneration,
                'prompt' => <<<'PROMPT'
Split this Klaus vom Amt script into short punchy on-screen caption chunks for vertical video.
Script:
{{script}}

Return valid JSON only:
{
  "chunks": ["line 1", "line 2"]
}
PROMPT,
            ],
            [
                'name' => 'Klaus Social Post Copy',
                'workflow_step' => WorkflowStep::SocialPostGeneration,
                'prompt' => <<<'PROMPT'
Create platform-ready post copy for Klaus vom Amt.

Title: {{title}}
Script: {{script}}

Return valid JSON only:
{
  "publish_title": "...",
  "publish_description": "...",
  "hashtags": ["KlausVomAmt", "Germany", "Bureaucracy"]
}
PROMPT,
            ],
        ];

        foreach ($templates as $template) {
            PromptTemplate::query()->updateOrCreate(
                [
                    'name' => $template['name'],
                    'workflow_step' => $template['workflow_step'],
                ],
                [
                    'prompt' => $template['prompt'],
                    'is_active' => true,
                ],
            );
        }
    }
}
