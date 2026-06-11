<?php

namespace App\Jobs;

use App\Enums\SocialPostStatus;
use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\VideoIdea;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SchedulePostsJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 120;

    public function __construct(public VideoIdea $videoIdea) {}

    public function handle(): void
    {
        $run = $this->startWorkflowRun($this->videoIdea, WorkflowStep::SocialPostGeneration);

        try {
            $accounts = SocialAccount::query()->where('is_active', true)->get();
            $scheduledAt = $this->videoIdea->scheduled_at ?? now()->addHour();
            $created = [];

            foreach ($accounts as $account) {
                $post = SocialPost::query()->updateOrCreate(
                    [
                        'video_idea_id' => $this->videoIdea->id,
                        'social_account_id' => $account->id,
                    ],
                    [
                        'platform' => $account->platform,
                        'status' => SocialPostStatus::Scheduled,
                        'scheduled_at' => $scheduledAt,
                        'error_message' => null,
                    ],
                );

                $created[] = $post->id;
            }

            $this->videoIdea->update([
                'status' => VideoIdeaStatus::Scheduled,
                'scheduled_at' => $scheduledAt,
                'error_message' => null,
            ]);

            $this->completeWorkflowRun(['social_post_ids' => $created]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception, $this->videoIdea);
            throw $exception;
        }
    }
}
