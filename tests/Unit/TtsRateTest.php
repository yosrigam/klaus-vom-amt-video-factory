<?php

namespace Tests\Unit;

use App\Support\TtsRate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TtsRateTest extends TestCase
{
    #[Test]
    public function it_applies_global_rate_offset(): void
    {
        config(['klaus.edge_tts_rate' => '-8%']);

        $this->assertSame('-12%', TtsRate::withGlobalOffset('-4%'));
        $this->assertSame('+2%', TtsRate::withGlobalOffset('+10%'));
    }

    #[Test]
    public function it_clamps_extreme_combined_rates(): void
    {
        config(['klaus.edge_tts_rate' => '-40%']);

        $this->assertSame('-50%', TtsRate::withGlobalOffset('-20%'));
    }
}
