<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void {}
}

class TestBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function broadcastOn(): array
    {
        return [new Channel('test-channel')];
    }

    public function broadcastAs(): string
    {
        return 'test.event';
    }
}

class QueueAndBroadcastTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dispatched_job_is_stored_in_database_queue(): void
    {
        config(['queue.default' => 'database']);

        TestQueueJob::dispatch();

        $this->assertDatabaseHas('jobs', [
            'queue' => 'default',
        ]);
    }

    /** @test */
    public function broadcast_event_is_dispatched(): void
    {
        Event::fake();

        event(new TestBroadcastEvent);

        Event::assertDispatched(TestBroadcastEvent::class);
    }

    /** @test */
    public function bus_fake_intercepts_queued_jobs(): void
    {
        Bus::fake();

        TestQueueJob::dispatch();

        Bus::assertDispatched(TestQueueJob::class);
    }
}
