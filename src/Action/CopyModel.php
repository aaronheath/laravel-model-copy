<?php

namespace Heath\LaravelModelCopy\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CopyModel
{
    protected Model $model;
    protected string $toModel;
    protected bool $deleteOriginal = false;

    public function copy(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    public function to(string $toModel)
    {
        $this->toModel = $toModel;

        return $this;
    }

    public function deleteOriginal()
    {
        $this->deleteOriginal = true;

        return $this;
    }

    public function run()
    {
        // TODO check that we have a model to copy and a model to copy to.

        // TODO check that to model has columns of original model. Use caching.

        DB::table(app(DescribeModel::class)->setModel($this->toModel)->table())
            ->insert($this->model->getAttributes());

        // TODO check that model has been copied

        if($this->deleteOriginal) {
            $this->model->forceDelete();

            // TODO check that model has been deleted
        }
    }


}