<?php

namespace Tests;

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use Heath\LaravelModelCopy\Action\CopyModel;
use Heath\LaravelModelCopy\Exception\LaravelModelCopyValidationException;
use Illuminate\Support\Facades\DB;
use Tests\Models\ExampleA;
use Tests\Models\ExampleB;
use Tests\Models\ExampleC;

class BatchCopyModelsTest extends TestCase
{
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
}