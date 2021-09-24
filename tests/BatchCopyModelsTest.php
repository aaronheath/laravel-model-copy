<?php

namespace Tests;

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use Heath\LaravelModelCopy\Action\CopyModel;
use Heath\LaravelModelCopy\Exception\LaravelModelCopyValidationException;
use Heath\LaravelModelCopy\Jobs\CopyModelJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;
use Tests\Models\ExampleA;
use Tests\Models\ExampleB;
use Tests\Models\ExampleC;

class BatchCopyModelsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function copies_batch_of_models()
    {
        ExampleA::factory()->count(350)->create();

        BatchCopyModels::make()
            ->to(ExampleB::class)
            ->query(
                ExampleA::whereB(true)
            )
            ->run();

        $this->assertEquals(
            DB::table('example_a')->whereB(true)->count(),
            DB::table('example_b')->whereB(true)->count()
        );
    }

    /**
     * @test
     */
    public function copies_batch_of_models_and_deletes_original()
    {
        ExampleA::factory()->count($originalCount = 350)->create();

        $countToBeMoved = DB::table('example_a')->whereB(true)->count();

        BatchCopyModels::make()
            ->to(ExampleB::class)
            ->query(
                ExampleA::whereB(true)
            )
            ->deleteOriginal()
            ->run();

        $this->assertEquals(
            $countToBeMoved,
            DB::table('example_b')->whereB(true)->count()
        );

        $this->assertEquals(
            $originalCount - $countToBeMoved,
            DB::table('example_a')->count()
        );
    }

    /**
     * @test
     */
    public function copies_batch_of_models_to_limit_less_than_chunk()
    {
        ExampleA::factory()->count(350)->create();

        BatchCopyModels::make()
            ->to(ExampleB::class)
            ->limit(50)
            ->query(
                ExampleA::whereB(true)
            )
            ->run();

        $this->assertEquals(
            50,
            DB::table('example_b')->whereB(true)->count()
        );
    }

    /**
     * @test
     */
    public function copies_batch_of_models_to_limit_greater_than_chunk()
    {
        ExampleA::factory()->count(350)->create();

        BatchCopyModels::make()
            ->to(ExampleB::class)
            ->limit(50)
            ->chunkSize(15)
            ->query(
                ExampleA::whereB(true)
            )
            ->run();

        $this->assertEquals(
            50,
            DB::table('example_b')->whereB(true)->count()
        );
    }

    /**
     * @test
     */
    public function pushes_individual_jobs_to_queue_for_copies()
    {
        Queue::fake();

        ExampleA::factory()->count(50)->create();

        $modelToCopy = ExampleA::whereB(true)->first();

        BatchCopyModels::make()
            ->to(ExampleB::class)
            ->query(
                ExampleA::whereB(true)
            )
            ->copyModelsAsJobs()
            ->run();

        Queue::assertPushed(CopyModelJob::class, DB::table('example_a')->whereB(true)->count());

        Queue::assertPushed(function(CopyModelJob $job) use ($modelToCopy) {
            $copyModel = $job->getCopyModel();

            if($copyModel->toModel !== ExampleB::class) {
                return false;
            }

            if(! $copyModel->fromModel instanceof ExampleA) {
                return false;
            }

            if($copyModel->fromModel->id !== $modelToCopy->id) {
                return false;
            }

            return true;
        });
    }
}