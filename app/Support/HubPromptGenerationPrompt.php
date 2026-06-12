<?php

namespace App\Support;

class HubPromptGenerationPrompt
{
    public static function render(?string $ideaText): ?string
    {
        $ideaText = trim((string) $ideaText);

        if ($ideaText === '') {
            return null;
        }

        $disclaimer = config('klaus.disclaimer');

        return <<<PROMPT
Write a Klaus vom Amt short-form vertical video (9:16) based on the idea below.

IDEA
{$ideaText}

Tone:
Sarcastic. Passive-aggressive. Deadpan. Bureaucratic. Dark humor. Mildly disappointed. Klaus is always convinced the system makes perfect sense.

Character Voice:

* First-person Klaus vom Amt narration.
* English only — the entire script body must be written in English. No German words, phrases, or sentences in the script output. (German intro/outro bookends are added automatically by the system — do not include them.)
* Sounds like an experienced civil servant explaining an obvious rule.
* Calm and matter-of-fact, even when the logic is absurd.
* Never self-aware.
* No shouting, memes, or internet slang.

KLAUS PERSONALITY

Klaus believes every rule exists for a good reason, even when the reason is obviously absurd.

Core traits:

Bureaucracy is more important than common sense.
Process is more important than outcomes.
Rules are always correct.
Contradictory rules are still correct.
Citizen inconvenience is unfortunate but necessary.
He never gets angry.
He never laughs at his own jokes.
He explains absurd situations as if they are perfectly reasonable.
He is mildly disappointed when citizens expect logic.
He treats obvious nonsense as standard procedure.

Comedy rules:

The joke comes from Klaus sincerely defending absurd bureaucracy.
Klaus is never the victim.
Klaus never realizes the contradiction.
Every story should escalate bureaucratic logic until it becomes ridiculous.
The ending should feel inevitable from Klaus's perspective.

Structure:

1. Hook (first sentence).
2. Bureaucratic rejection.
3. Escalation of the contradiction.
4. Final absurd justification.
5. Dry punchline.

Requirements:

* Length: 40–50 seconds when read aloud.
* Output a single first-person voiceover monologue.
* Short sentences only.
* Natural English only — never German.
* Correct punctuation.
* Optimized for text-to-speech.
* {$disclaimer}

Image Prompt Requirements:

* Create one scene-specific image prompt for a vertical 9:16 frame.
* Describe pose, props, composition, and the visual joke.
* Include exactly one solid, vibrant background color.
* Do NOT describe Klaus's face, hair, age, glasses, clothing colors, or illustration style.
* The Klaus character design is injected automatically.

Return valid JSON only:

{
  "script": "...",
  "image_prompt": "..."
}
PROMPT;
    }
}
