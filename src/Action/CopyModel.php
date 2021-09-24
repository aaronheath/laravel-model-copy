<?php

namespace Heath\LaravelModelCopy\Action;

use Heath\LaravelModelCopy\Exception\LaravelModelCopyValidationException;
use Heath\LaravelModelCopy\Traits\SetupHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CopyModel
{
    use SetupHelper;

//    protected Model $fromModel;
//    protected string $toModel;
//    protected bool $deleteOriginal = false;
//
    static public function make(): CopyModel
    {
        return app(CopyModel::class);
    }
//
//    public function copy(Model $fromModel)
//    {
//        $this->fromModel = $fromModel;
//
//        return $this;
//    }
//
//    public function to(string $toModel)
//    {
//        $this->toModel = $toModel;
//
//        return $this;
//    }
//
//    public function deleteOriginal()
//    {
//        $this->deleteOriginal = true;
//
//        return $this;
//    }

    public function when($value, callable $fn)
    {
        if(! $value) {
            return $this;
        }

        return $fn($this);

//        return $this;
    }

    public function run()
    {
        $this->validate();

        $this->performCopy();

        $this->confirmModelCopied();

        if($this->deleteOriginal) {
            $this->fromModel->forceDelete();

            $this->confirmOriginalModelDeleted();
        }
    }

    protected function validate()
    {
        $this->validateInput();

        $this->validateColumns();
    }

    protected function validateInput()
    {
        if(! isset($this->fromModel)) {
            throw new LaravelModelCopyValidationException(
                'Unable to copy model as original model class hasn\'t been defined.'
            );
        }

        if(! isset($this->toModel)) {
            throw new LaravelModelCopyValidationException(
                'Unable to copy model as new model class hasn\'t been defined.'
            );
        }

        if(! class_exists($this->toModel)) {
            throw new LaravelModelCopyValidationException(
                sprintf(
                    'Unable to copy model as new model class doesn\'t exist. Model: %s, ID: %s',
                    get_class($this->fromModel),
                    $this->fromModel->id
                )
            );
        }
    }

    protected function validateColumns()
    {
        $fromColumns = app(DescribeModel::class)->setModel($this->fromModel)->columns();

        $toColumns = app(DescribeModel::class)->setModel($this->toModel)->columns();

        $diff = collect($fromColumns)->diff($toColumns);

        if($diff->isEmpty()) {
            return;
        }

        throw new LaravelModelCopyValidationException(
            sprintf(
                'Unable to copy model as new table doesn\'t contain all columns of the original table. Model: %s, ID: %s, Columns: %s',
                get_class($this->fromModel),
                $this->fromModel->id,
                $diff->implode(', ')
            )
        );
    }

    protected function performCopy()
    {
        DB::table(app(DescribeModel::class)->setModel($this->toModel)->table())
            ->updateOrInsert(
                ['id' => $this->fromModel->getAttribute('id')],
                $this->fromModel->getAttributes()
            );
    }

    protected function confirmModelCopied()
    {
        $newRecord = DB::table(app(DescribeModel::class)->setModel($this->toModel)->table())
            ->find($this->fromModel->getAttribute('id'));

        if(is_null($newRecord)) {
            throw new LaravelModelCopyValidationException(
                sprintf(
                    'Model copy failed. Original copy has not been removed. Model: %s, ID: %s',
                    get_class($this->fromModel),
                    $this->fromModel->id
                )
            );
        };
    }

    protected function confirmOriginalModelDeleted()
    {
        $newRecord = DB::table(app(DescribeModel::class)->setModel($this->fromModel)->table())
            ->find($this->fromModel->getAttribute('id'));

        if(! is_null($newRecord)) {
            throw new LaravelModelCopyValidationException(
                sprintf(
                    'Model deletion has failed. Original copy has not been removed. Model: %s, ID: %s',
                    get_class($this->fromModel),
                    $this->fromModel->id
                )
            );
        };
    }
}