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

        $fromModels = ExampleA::factory()->count(350)->create();

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

//        $fromRecord = DB::table('example_a')->find($fromModel->id);
//        $this->assertNotNull($fromRecord);
//
//        CopyModel::make()->copy($fromModel)->to(ExampleB::class)->run();
//
//        $toRecord = DB::table('example_b')->find($fromModel->id);
//
//        $this->assertNotNull($toRecord);
//
//        $this->assertEquals($fromRecord->a, $toRecord->a);
//        $this->assertEquals($fromRecord->b, $toRecord->b);
//        $this->assertEquals($fromRecord->c, $toRecord->c);
//        $this->assertNull($toRecord->deleted_at);
//        $this->assertEquals($fromRecord->created_at, $toRecord->created_at);
//        $this->assertEquals($fromRecord->updated_at, $toRecord->updated_at);
    }
}