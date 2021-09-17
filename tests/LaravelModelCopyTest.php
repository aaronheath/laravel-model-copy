<?php

namespace Tests;

use Heath\LaravelModelCopy\Action\DescribeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Models\ExampleA;

class LaravelModelCopyTest extends TestCase
{
    /**
     * @test
     */
    public function copies_model_from_one_table_to_another()
    {
         app(DescribeModel::class)->setModel(ExampleA::class)->columns();

        ExampleA::create([
            'a' => 'Hello',
            'b' => true,
            'c' => 'Goodbye',
        ]);

        ExampleA::create([
            'a' => 'Essendon',
            'b' => false,
        ]);

        $this->assertEquals(2, ExampleA::count());
    }
}