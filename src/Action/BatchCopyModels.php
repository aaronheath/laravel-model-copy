<?php

namespace Heath\LaravelModelCopy\Action;

use Carbon\CarbonImmutable;
use Heath\LaravelModelCopy\Exception\LaravelBatchCopyModelsValidationException;
use Heath\LaravelModelCopy\Jobs\CopyModelJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BatchCopyModels
{
    protected string $toModel;
    protected Builder $query;
    protected bool $deleteOriginal = false;
    protected int $chunkSize = 100;
    protected int $count = 0;
    protected int $limit = 0;
    protected bool $copyModelsAsJobs = false;
    protected string $queue;
    protected Carbon $processBefore;
    protected int $rpm;
    protected CarbonImmutable $delayingUntil;
    protected CarbonImmutable $startedAt;

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

    public function processBefore(Carbon $processBefore)
    {
        $this->processBefore = $processBefore;

        return $this;
    }

    public function rpm(int $rpm)
    {
        $this->rpm = $rpm;

        return $this;
    }

    public function copyModelsAsJobs()
    {
        $this->copyModelsAsJobs = true;

        return $this;
    }

    public function onQueue(string $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    public function run()
    {
        $this->startedAt = now()->toImmutable();

        $this->validate();

        $this->performBatch();
    }

    protected function validate()
    {
        $this->validateInput();
    }

    protected function validateInput()
    {
        if(! isset($this->query)) {
            throw new LaravelBatchCopyModelsValidationException(
                'Unable to batch copy models as query hasn\'t been defined.'
            );
        }

        if(! isset($this->toModel)) {
            throw new LaravelBatchCopyModelsValidationException(
                'Unable to batch copy models as new model class hasn\'t been defined.'
            );
        }

        if(! class_exists($this->toModel)) {
            throw new LaravelBatchCopyModelsValidationException(
                'Unable to batch copy models as new model class doesn\'t exist. Class: '
                . $this->toModel
            );
        }

        if($this->chunkSize <= 1) {
            throw new LaravelBatchCopyModelsValidationException(
                'Unable to batch copy models as chunk size must be greater than 1. Size: '
                . $this->chunkSize
            );
        }

        if($this->limit < 0) {
            throw new LaravelBatchCopyModelsValidationException(
                'Unable to batch copy models as limit size must be greater than 0. Size: '
                . $this->limit
            );
        }

        if(isset($this->rpm) && $this->rpm < 0) {
            throw new LaravelBatchCopyModelsValidationException(
                'Unable to batch copy models as rate per minute (rpm) must be greater than 0. Size: '
                . $this->rpm
            );
        }
    }

    protected function performBatch()
    {
        $this
            ->query
            ->chunkById($this->chunkSize, function($items) {
                if($this->hasReachedLimit()) {
                    return false;
                }

                if($this->delayHasExceededProcessBefore()) {
                    return false;
                }

                $this->copyModels($items);
            });
    }

    protected function copyModels(Collection $models)
    {
        $models->each(fn($model) => $this->copyModel($model));
    }

    protected function copyModel(Model $model)
    {
        $this->incrementCount();

        if($this->hasReachedLimit()) {
            return false;
        }

        if($this->delayHasExceededProcessBefore()) {
            return false;
        }

        $copyModel = CopyModel::make()
            ->copy($model)
            ->to($this->toModel)
            ->when($this->deleteOriginal, fn($self) => $self->deleteOriginal())
            ->when(isset($this->processBefore), fn($self) => $self->processBefore($this->processBefore));

        $this->copyModelsAsJobs ? $this->copyAsJobs($copyModel) : $this->copySync($copyModel);
    }

    protected function incrementCount()
    {
        $this->count++;
    }

    protected function hasReachedLimit()
    {
        if(! $this->limit) {
            return false;
        }

        return $this->count > $this->limit;
    }

    protected function delayHasExceededProcessBefore()
    {
        if(! isset($this->delayingUntil)) {
            return false;
        }

        if(! isset($this->processBefore)) {
            return false;
        }

        return $this->delayingUntil->gt($this->processBefore);
    }

    protected function copyAsJobs(CopyModel $copyModel)
    {
        $job = CopyModelJob::dispatch($copyModel);

        if(isset($this->rpm)) {
            $job->delay($this->delayUntil());
        }

        if(isset($this->queue)) {
            $job->onQueue($this->queue);
        }
    }

    protected function copySync(CopyModel $copyModel)
    {
        $copyModel->run();
    }

    protected function delayUntil()
    {
        $this->delayingUntil = $this->startedAt->addSeconds(floor(60 / $this->rpm * $this->count));

        return $this->delayingUntil;
    }
}