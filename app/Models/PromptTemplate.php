<?php

namespace App\Models;

use App\Enums\WorkflowStep;
use Illuminate\Database\Eloquent\Model;

class PromptTemplate extends Model
{
    protected $fillable = [
        'name',
        'workflow_step',
        'prompt',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'workflow_step' => WorkflowStep::class,
            'is_active' => 'boolean',
        ];
    }

    public static function activeFor(WorkflowStep $step): ?self
    {
        return static::query()
            ->where('workflow_step', $step)
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    public function render(array $variables): string
    {
        $prompt = $this->prompt;

        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{'.$key.'}}', (string) $value, $prompt);
        }

        return $prompt;
    }
}
