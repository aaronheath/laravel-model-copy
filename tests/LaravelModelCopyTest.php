<?php

namespace Tests;

use Heath\LaravelModelCopy\Action\CopyModel;
use Heath\LaravelModelCopy\Exception\LaravelModelCopyValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Models\ExampleA;
use Tests\Models\ExampleB;
use Tests\Models\ExampleC;

class LaravelModelCopyTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function copies_model_from_one_table_to_another()
    {
        $fromModel = ExampleA::factory()->create();

        $fromRecord = DB::table('example_a')->find($fromModel->id);
        $this->assertNotNull($fromRecord);

        CopyModel::make()->copy($fromModel)->to(ExampleB::class)->run();

        $toRecord = DB::table('example_b')->find($fromModel->id);

        $this->assertNotNull($toRecord);

        $this->assertEquals($fromRecord->a, $toRecord->a);
        $this->assertEquals($fromRecord->b, $toRecord->b);
        $this->assertEquals($fromRecord->c, $toRecord->c);
        $this->assertNull($toRecord->deleted_at);
        $this->assertEquals($fromRecord->created_at, $toRecord->created_at);
        $this->assertEquals($fromRecord->updated_at, $toRecord->updated_at);
    }

    /**
     * @test
     */
    public function copies_soft_deleted_model_from_one_table_to_another()
    {
        $fromModel = ExampleA::factory()->create();

        $fromModel->delete();

        $fromRecord = DB::table('example_a')->find($fromModel->id);
        $this->assertNotNull($fromRecord);

        CopyModel::make()->copy($fromModel)->to(ExampleB::class)->run();

        $toRecord = DB::table('example_b')->find($fromModel->id);
        $this->assertNotNull($toRecord);

        $this->assertEquals($fromRecord->a, $toRecord->a);
        $this->assertEquals($fromRecord->b, $toRecord->b);
        $this->assertEquals($fromRecord->c, $toRecord->c);
        $this->assertEquals($fromRecord->deleted_at, $toRecord->deleted_at);
        $this->assertEquals($fromRecord->created_at, $toRecord->created_at);
        $this->assertEquals($fromRecord->updated_at, $toRecord->updated_at);
    }

    /**
     * @test
     */
    public function deletes_original_model_when_requested()
    {
        $fromModel = ExampleA::factory()->create();

        $fromModel->delete();

        $fromRecord = DB::table('example_a')->find($fromModel->id);
        $this->assertNotNull($fromRecord);

        CopyModel::make()->copy($fromModel)->to(ExampleB::class)->deleteOriginal()->run();

        $toRecord = DB::table('example_b')->find($fromModel->id);
        $this->assertNotNull($toRecord);

        $fromRecord = DB::table('example_a')->find($fromModel->id);
        $this->assertNull($fromRecord);
    }

    /**
     * @test
     */
    public function processes_when_expiry_is_in_future()
    {
        $fromModel = ExampleA::factory()->create();

        DB::table('example_a')->find($fromModel->id);

        CopyModel::make()->copy($fromModel)->to(ExampleB::class)->processBefore(now()->addHour())->run();

        $toRecord = DB::table('example_b')->find($fromModel->id);

        $this->assertNotNull($toRecord);
    }

    /**
     * @test
     */
    public function doesnt_processes_when_expiry_is_in_past()
    {
        $fromModel = ExampleA::factory()->create();

        DB::table('example_a')->find($fromModel->id);

        CopyModel::make()->copy($fromModel)->to(ExampleB::class)->processBefore(now()->subHour())->run();

        $toRecord = DB::table('example_b')->find($fromModel->id);

        $this->assertNull($toRecord);
    }

    /**
     * @test
     */
    public function fails_copy_when_from_model_not_defined()
    {
        $this->expectException(LaravelModelCopyValidationException::class);
        $this->expectExceptionMessage('Unable to copy model as original model class hasn\'t been defined.');

        CopyModel::make()->to(ExampleB::class)->run();
    }

    /**
     * @test
     */
    public function fails_copy_when_to_model_not_defined()
    {
        $this->expectException(LaravelModelCopyValidationException::class);
        $this->expectExceptionMessage('Unable to copy model as new model class hasn\'t been defined.');

        $fromModel = ExampleA::create([
            'a' => 'Hello',
            'b' => true,
            'c' => 'Goodbye',
        ]);

        CopyModel::make()->copy($fromModel)->run();
    }

    /**
     * @test
     */
    public function fails_copy_when_to_model_isnt_model()
    {
        $fromModel = ExampleA::factory()->create();

        $this->expectException(LaravelModelCopyValidationException::class);
        $this->expectExceptionMessage('Unable to copy model as new model class doesn\'t exist. Model: Tests\Models\ExampleA, ID: ' . $fromModel->id);

        CopyModel::make()->copy($fromModel)->to('Tests\Models\DoesntExist')->run();
    }

    /**
     * @test
     */
    public function fails_copy_when_to_table_doesnt_have_same_columns()
    {
        $fromModel = ExampleA::factory()->create();

        $this->expectException(LaravelModelCopyValidationException::class);
        $this->expectExceptionMessage('Unable to copy model as new table doesn\'t contain all columns of the original table. Model: Tests\Models\ExampleA, ID: ' . $fromModel->id . ', Columns: b, c');

        CopyModel::make()->copy($fromModel)->to(ExampleC::class)->run();
    }
}