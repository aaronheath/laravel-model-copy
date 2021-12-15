<?php

namespace Heath\LaravelModelCopy\Action;

use Heath\LaravelModelCopy\Exception\LaravelModelDeleteValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteModel
{
    protected Model $model;
    protected Carbon $processBefore;
    protected string $modelClass;
    protected $modelKey;

    static public function make(): DeleteModel
    {
        return app(DeleteModel::class);
    }

    public function __get($key)
    {
        return $this->{$key} ?? null;
    }

    public function delete(Model $model)
    {
        $this->modelClass = get_class($model);
        $this->modelKey = $model->getKey();

        return $this;
    }

    public function processBefore(Carbon $processBefore)
    {
        $this->processBefore = $processBefore;

        return $this;
    }

    public function when($value, callable $fn)
    {
        if(! $value) {
            return $this;
        }

        return $fn($this);
    }

    public function run()
    {
        if($this->isExpired()) {
            return;
        }

        $this->hydrateModel();

        $this->validate();

        $this->performDelete();

        $this->confirmModelDeleted();
    }

    public function hydrateModel()
    {
        if(! isset($this->modelClass) || ! isset($this->modelKey)) {
            throw new LaravelModelDeleteValidationException(
                'Unable to delete model as model hasn\'t been defined.'
            );
        }

        $query = $this->modelClass::whereKey($this->modelKey);

        if(in_array(SoftDeletes::class, class_uses($this->modelClass))) {
            $query->withTrashed();
        }

        $this->model = $query->first();

        return $this;
    }

    protected function validate()
    {
        $this->validateInput();
    }

    protected function isExpired()
    {
        if(! isset($this->processBefore)) {
            return false;
        }

        return $this->processBefore->isBefore(now());
    }

    protected function validateInput()
    {
        //
    }

    protected function performDelete()
    {
        $this->model->forceDelete();
    }

    protected function confirmModelDeleted()
    {
        $record = DB::table(app(DescribeModel::class)->setModel($this->model)->table())
            ->find($this->model->getAttribute('id'));

        if(! is_null($record)) {
            throw new LaravelModelDeleteValidationException(
                sprintf(
                    'Model deletion has failed. Model: %s, ID: %s',
                    get_class($this->model),
                    $this->model->id
                )
            );
        };
    }
}