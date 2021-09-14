<?php

namespace Heath\LaravelModelCopy\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DescribeModel
{
    protected Model $model;

    public function setModel(string $path)
    {
        $this->model = (new $path);

        return $this;
    }

    public function columns()
    {
        $table = $this->model->getTable();

        return DB::getSchemaBuilder()->getColumnListing($table);;
    }
}