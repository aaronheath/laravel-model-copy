<?php

namespace Heath\LaravelModelCopy\Action;

use Heath\LaravelModelCopy\Exception\LaravelModelCopyValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DescribeModel
{
    protected Model $model;

    public function setModel($model)
    {
        if($model instanceof Model) {
            $this->model = $model;
        }

        if(is_string($model)) {
            $this->model = (new $model);
        }

        if(! $this->model instanceof Model) {
            throw new LaravelModelCopyValidationException('$this->model is not instance of Eloquent model.');
        }

        return $this;
    }

    public function columns()
    {
        return DB::getSchemaBuilder()
            ->getColumnListing($this->table());
    }

    public function table()
    {
        return $this->model->getTable();
    }
}