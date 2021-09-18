<?php

namespace Tests;

use Heath\LaravelModelCopy\Action\CopyModel;
use Heath\LaravelModelCopy\Action\DescribeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Models\ExampleA;
use Tests\Models\ExampleB;

class LaravelModelCopyTest extends TestCase
{
    /**
     * @test
     */
    public function copies_model_from_one_table_to_another()
    {
        $fromModel = ExampleA::create([
            'a' => 'Hello',
            'b' => true,
            'c' => 'Goodbye',
        ]);

        $this->assertNull(ExampleB::find($fromModel->id));

        app(CopyModel::class)->copy($fromModel)->to(ExampleB::class)->run();

        $this->assertInstanceOf(ExampleA::class, ExampleA::find($fromModel->id));
        $this->assertInstanceOf(ExampleB::class, ExampleB::find($fromModel->id));
    }

    /**
     * @test
     */
    public function copies_soft_deleted_model_from_one_table_to_another()
    {

    }

    /**
     * @test
     */
    public function deletes_orginal_model_when_requested()
    {

    }

    /**
     * @test
     */
    public function fails_copy_when_from_model_not_defined()
    {

    }

    /**
     * @test
     */
    public function fails_copy_when_to_model_not_defined()
    {

    }

    /**
     * @test
     */
    public function fails_copy_when_to_model_isnt_model()
    {

    }

    /**
     * @test
     */
    public function fails_copy_when_to_table_doesnt_have_same_columns()
    {

    }
}