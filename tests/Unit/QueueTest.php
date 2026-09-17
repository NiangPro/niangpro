<?php

namespace Tests\Unit;

use Niang\Core\Job;
use Niang\Core\Queue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearQueueDir();
    }

    protected function tearDown(): void
    {
        $this->clearQueueDir();
        parent::tearDown();
    }

    private function clearQueueDir(): void
    {
        $dir = base_path('storage/framework/queue');

        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
    }

    public function test_push_then_work_processes_the_job(): void
    {
        Queue::push(new QueueTestSucceedingJob());

        $this->assertSame(1, Queue::pending());
        $this->assertSame(1, Queue::work());
        $this->assertSame(0, Queue::pending());
        $this->assertSame([], Queue::failed());
    }

    public function test_later_delays_the_job_until_due(): void
    {
        Queue::later(3600, new QueueTestSucceedingJob());

        $this->assertSame(1, Queue::pending());
        $this->assertSame(0, Queue::work()); // pas encore dû : work() ne doit pas le traiter
        $this->assertSame(1, Queue::pending()); // toujours en attente, pas perdu
    }

    public function test_a_job_that_exhausts_its_tries_ends_up_in_failed(): void
    {
        Queue::push(new QueueTestFailingJob());

        $this->assertSame(0, Queue::work());
        $this->assertSame(0, Queue::pending());

        $failed = Queue::failed();
        $this->assertCount(1, $failed);
        $this->assertSame(QueueTestFailingJob::class, $failed[0]['class']);
        $this->assertStringContainsString('boom', $failed[0]['error']);
    }

    public function test_a_job_with_remaining_tries_is_rescheduled_instead_of_failed(): void
    {
        $job = new QueueTestFailingJob();
        $job->tries = 3;

        Queue::push($job);
        Queue::work();

        // Retentée plus tard (backoff), pas encore dans les échecs, toujours comptée en attente.
        $this->assertSame([], Queue::failed());
        $this->assertSame(1, Queue::pending());
    }

    public function test_retry_puts_a_failed_job_back_in_the_queue(): void
    {
        Queue::push(new QueueTestFailingJob());
        Queue::work();

        $id = Queue::failed()[0]['id'];

        $this->assertTrue(Queue::retry($id));
        $this->assertSame([], Queue::failed());
        $this->assertSame(1, Queue::pending());
    }

    public function test_retry_returns_false_for_an_unknown_id(): void
    {
        $this->assertFalse(Queue::retry('does-not-exist'));
    }

    public function test_flush_deletes_all_failed_jobs(): void
    {
        Queue::push(new QueueTestFailingJob());
        Queue::push(new QueueTestFailingJob());
        Queue::work();

        $this->assertCount(2, Queue::failed());
        $this->assertSame(2, Queue::flush());
        $this->assertSame([], Queue::failed());
    }
}

class QueueTestSucceedingJob extends Job
{
    public function handle(): void
    {
    }
}

class QueueTestFailingJob extends Job
{
    public function handle(): void
    {
        throw new \RuntimeException('boom');
    }
}
