<?php

namespace Heath\LaravelModelCopy\Action;

use Heath\LaravelModelCopy\Exception\LaravelModelCopyValidationException;
use Heath\LaravelModelCopy\Jobs\CopyModelJob;
use Illuminate\Database\Eloquent\Builder;

class BatchCopyModels
{
    protected string $toModel;
    protected bool $deleteOriginal = false;
    protected int $chunkSize = 100;
    protected Builder $query;
    protected int $count = 0;
    protected int $limit = 0;
    protected bool $copyModelsAsJobs = false;

    static public function make(): BatchCopyModels
    {
        return app(BatchCopyModels::class);
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

    public function chunkSize(int $chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    public function query(Builder $query)
    {
        $this->query = $query;

        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function asJob(string $queue = null)
    {

    }

    public function copyModelsAsJobs()
    {
        $this->copyModelsAsJobs = true;

        return $this;
    }

    public function run()
    {
//        $this->validate();

//        dd($this->query);

        $this
            ->query
            ->chunkById($this->chunkSize, function($items) {
                if($this->hasReachedLimit()) {
                    return false;
                }

                $items->each(function($item) {
                    $this->incrementCount();

                    if($this->hasReachedLimit()) {
                        return false;
                    }

                    $copyModel = CopyModel::make()
                        ->copy($item)
                        ->to($this->toModel)
                        ->when($this->deleteOriginal, fn($self) => $self->deleteOriginal());

                    $this->copyModelsAsJobs ? CopyModelJob::dispatch($copyModel) : $copyModel->run();
                });
            });

//        $this->validate();
//
//        $this->performCopy();
//
//        $this->confirmModelCopied();
//
//        if($this->deleteOriginal) {
//            $this->fromModel->forceDelete();
//
//            $this->confirmOriginalModelDeleted();
//        }
    }

    public function incrementCount()
    {
        $this->count++;
    }

    public function hasReachedLimit()
    {
        if(! $this->limit) {
            return false;
        }

        return $this->count > $this->limit;
    }


    protected function validate()
    {
        $this->validateInput();
    }
//
    protected function validateInput()
    {
//        if(! isset($this->fromModel)) {
//            throw new LaravelModelCopyValidationException(
//                'Unable to copy model as original model class hasn\'t been defined.'
//            );
//        }

        if(! isset($this->query)) {
            throw new LaravelModelCopyValidationException(
                'Unable to copy model as new model class hasn\'t been defined.'
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
//
//    protected function validateColumns()
//    {
//        $fromColumns = app(DescribeModel::class)->setModel($this->fromModel)->columns();
//
//        $toColumns = app(DescribeModel::class)->setModel($this->toModel)->columns();
//
//        $diff = collect($fromColumns)->diff($toColumns);
//
//        if($diff->isEmpty()) {
//            return;
//        }
//
//        throw new LaravelModelCopyValidationException(
//            sprintf(
//                'Unable to copy model as new table doesn\'t contain all columns of the original table. Model: %s, ID: %s, Columns: %s',
//                get_class($this->fromModel),
//                $this->fromModel->id,
//                $diff->implode(', ')
//            )
//        );
//    }
//
//    protected function performCopy()
//    {
//        DB::table(app(DescribeModel::class)->setModel($this->toModel)->table())
//            ->updateOrInsert(
//                ['id' => $this->fromModel->getAttribute('id')],
//                $this->fromModel->getAttributes()
//            );
//    }
//
//    protected function confirmModelCopied()
//    {
//        $newRecord = DB::table(app(DescribeModel::class)->setModel($this->toModel)->table())
//            ->find($this->fromModel->getAttribute('id'));
//
//        if(is_null($newRecord)) {
//            throw new LaravelModelCopyValidationException(
//                sprintf(
//                    'Model copy failed. Original copy has not been removed. Model: %s, ID: %s',
//                    get_class($this->fromModel),
//                    $this->fromModel->id
//                )
//            );
//        };
//    }
//
//    protected function confirmOriginalModelDeleted()
//    {
//        $newRecord = DB::table(app(DescribeModel::class)->setModel($this->fromModel)->table())
//            ->find($this->fromModel->getAttribute('id'));
//
//        if(! is_null($newRecord)) {
//            throw new LaravelModelCopyValidationException(
//                sprintf(
//                    'Model deletion has failed. Original copy has not been removed. Model: %s, ID: %s',
//                    get_class($this->fromModel),
//                    $this->fromModel->id
//                )
//            );
//        };
//    }
}