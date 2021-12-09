<?php

namespace Tests;

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use Heath\LaravelModelCopy\Exception\LaravelBatchDeleteModelsValidationException;
use Heath\LaravelModelCopy\Jobs\CopyModelJob;
use Heath\LaravelModelCopy\Jobs\DeleteModelJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Models\ExampleA;

class BatchDeleteModelsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function deletes_batch_of_models()
    {
        ExampleA::factory()->count(350)->create();

        BatchDeleteModels::make()
            ->query(
                ExampleA::whereB(true)
            )
            ->run();

        $this->assertEquals(
            0,
            DB::table('example_a')->whereB(true)->count()
        );
    }

    /**
     * @test
     */
    public function deletes_batch_of_models_to_limit_less_than_chunk()
    {
        ExampleA::factory()->count(350)->create();

        $beforeCount = DB::table('example_a')->whereB(true)->count();

        BatchDeleteModels::make()
            ->limit(50)
            ->query(
                ExampleA::whereB(true)
            )
            ->run();

        $this->assertEquals(
            $beforeCount - 50,
            DB::table('example_a')->whereB(true)->count()
        );
    }

    /**
     * @test
     */
    public function deletes_batch_of_models_to_limit_greater_than_chunk()
    {
        ExampleA::factory()->count(350)->create();

        $beforeCount = DB::table('example_a')->whereB(true)->count();

        BatchDeleteModels::make()
            ->limit(50)
            ->chunkSize(15)
            ->query(
                ExampleA::whereB(true)
            )
            ->run();

        $this->assertEquals(
            $beforeCount - 50,
            DB::table('example_a')->whereB(true)->count()
        );
    }

    /**
     * @test
     */
    public function pushes_individual_jobs_to_queue_for_deletions()
    {
        Queue::fake();

        ExampleA::factory()->count(50)->create();

        $modelToDelete = ExampleA::whereB(true)->first();

        BatchDeleteModels::make()
            ->query(
                ExampleA::whereB(true)
            )
            ->deleteModelsAsJobs()
            ->run();

        Queue::assertPushed(DeleteModelJob::class, DB::table('example_a')->whereB(true)->count());

        Queue::assertPushed(function(DeleteModelJob $job) use ($modelToDelete) {
            $deleteModel = $job->getDeleteModel();

            if(! $deleteModel->model instanceof ExampleA) {
                return false;
            }

            if($deleteModel->model->id !== $modelToDelete->id) {
                return false;
            }

            return true;
        });
    }

    /**
     * @test
     */
    public function pushes_individual_jobs_to_specific_queue_for_deltion()
    {
        Queue::fake();

        ExampleA::factory()->count(50)->create();

        BatchDeleteModels::make()
            ->query(
                ExampleA::whereB(true)
            )
            ->onQueue('test-queue')
            ->deleteModelsAsJobs()
            ->run();

        Queue::assertPushedOn('test-queue', DeleteModelJob::class);
    }

    /**
     * @test
     */
    public function passes_through_process_until()
    {
        Queue::fake();

        $processBefore = now()->addHour();

        ExampleA::factory()->count(50)->create();

        BatchDeleteModels::make()
            ->query(
                ExampleA::whereB(true)
            )
            ->processBefore($processBefore)
            ->deleteModelsAsJobs()
            ->run();

        Queue::assertPushed(function(DeleteModelJob $job) use ($processBefore) {
            return $job->getDeleteModel()->processBefore->equalTo($processBefore);
        });
    }

    /**
     * @test
     */
    public function passes_through_delay_due_to_rpm()
    {
        Queue::fake();

        Carbon::setTestNow(now()->startOfMinute());

        ExampleA::factory()->count(20)->create();

        BatchDeleteModels::make()
            ->query(
                ExampleA::whereB(true)
            )
            ->rpm(3)
            ->deleteModelsAsJobs()
            ->run();

        Queue::assertPushed(function(DeleteModelJob $job) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute());
        });

        Queue::assertPushed(function(DeleteModelJob $job) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute()->addSeconds(20));
        });

        Queue::assertPushed(function(DeleteModelJob $job) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute()->addSeconds(40));
        });
    }

    /**
     * @test
     */
    public function passes_through_delay_due_to_rpm_where_greater_than_60()
    {
        Queue::fake();

        Carbon::setTestNow(now()->startOfMinute());

        ExampleA::factory()->count(300)->create();

        BatchDeleteModels::make()
            ->query(
                ExampleA::whereB(true)
            )
            ->rpm(100)
            ->deleteModelsAsJobs()
            ->run();

        Queue::assertPushed(function(DeleteModelJob $job) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute());
        });

        Queue::assertPushed(function(DeleteModelJob $job) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute()->addSeconds(2));
        });

        Queue::assertPushed(function(DeleteModelJob $job) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute()->addSeconds(5));
        });
    }

    /**
     * @test
     */
    public function passes_through_rpm_and_process_before()
    {
        Queue::fake();

        Carbon::setTestNow(now()->startOfMinute());

        $processBefore = now()->addMinutes(5);

        ExampleA::factory()->count(100)->create();

        BatchDeleteModels::make()
            ->query(
                ExampleA::whereB(true)
            )
            ->processBefore($processBefore)
            ->rpm(3)
            ->deleteModelsAsJobs()
            ->run();

        Queue::assertPushed(function(DeleteModelJob $job) use ($processBefore) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute())
                && $job->getDeleteModel()->processBefore->equalTo($processBefore);
        });

        Queue::assertPushed(function(DeleteModelJob $job) use ($processBefore) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute()->addSeconds(20))
                && $job->getDeleteModel()->processBefore->equalTo($processBefore);
        });

        Queue::assertPushed(function(DeleteModelJob $job) use ($processBefore) {
            return $job->delay->equalTo(now()->addMinute()->startOfMinute()->addSeconds(40))
                && $job->getDeleteModel()->processBefore->equalTo($processBefore);
        });

        Queue::assertPushed(DeleteModelJob::class, 16);
    }

    /**
     * @test
     */
    public function fails_batch_deletion_when_query_not_defined()
    {
        $this->expectException(LaravelBatchDeleteModelsValidationException::class);
        $this->expectExceptionMessage('Unable to batch delete models as query hasn\'t been defined.');

        BatchDeleteModels::make()
            ->run();
    }

    /**
     * @test
     */
    public function fails_batch_copy_when_chunk_size_is_not_greater_than_1()
    {
        $chunkSize = -3;

        $this->expectException(LaravelBatchDeleteModelsValidationException::class);
        $this->expectExceptionMessage('Unable to batch delete models as chunk size must be greater than 1. Size: ' . $chunkSize);

        BatchDeleteModels::make()
            ->chunkSize($chunkSize)
            ->query(
                ExampleA::whereB(true)
            )
            ->run();
    }

    /**
     * @test
     */
    public function fails_batch_copy_when_limit_is_not_greater_than_0()
    {
        $limit = -3;

        $this->expectException(LaravelBatchDeleteModelsValidationException::class);
        $this->expectExceptionMessage('Unable to batch delete models as limit size must be greater than 0. Size: ' . $limit);

        BatchDeleteModels::make()
            ->limit($limit)
            ->query(
                ExampleA::whereB(true)
            )
            ->run();
    }
}