<?php

namespace Tests;

use Heath\LaravelModelCopy\Action\DeleteModel;
use Heath\LaravelModelCopy\Exception\LaravelModelDeleteValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Models\ExampleA;
use Tests\Models\ExampleB;

class LaravelModelDeleteTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function deletes_model_with_soft_delete()
    {
        $model = ExampleA::factory()->create();

        $record = DB::table('example_a')->find($model->id);
        $this->assertNotNull($record);

        DeleteModel::make()->delete($model)->run();

        $nullModel = DB::table('example_a')->find($model->id);

        $this->assertNull($nullModel);
    }

    /**
     * @test
     */
    public function deletes_model_without_soft_delete()
    {
        $model = ExampleB::factory()->create();

        $record = DB::table('example_b')->find($model->id);
        $this->assertNotNull($record);

        DeleteModel::make()->delete($model)->run();

        $nullModel = DB::table('example_b')->find($model->id);

        $this->assertNull($nullModel);
    }

    /**
     * @test
     */
    public function swallows_delete_model_when_model_doesnt_exist()
    {
        $model = ExampleA::factory()->create();

        $record = DB::table('example_a')->find($model->id);
        $this->assertNotNull($record);

        $model->forceDelete();

        DeleteModel::make()->delete($model)->run();

        $nullModel = DB::table('example_a')->find($model->id);

        $this->assertNull($nullModel);
    }

    /**
     * @test
     */
    public function fails_delete_when_model_not_defined()
    {
        $this->expectException(LaravelModelDeleteValidationException::class);
        $this->expectExceptionMessage('Unable to delete model as model hasn\'t been defined.');

        DeleteModel::make()->run();
    }
}