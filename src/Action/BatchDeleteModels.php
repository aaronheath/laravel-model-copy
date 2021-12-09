<?php

namespace Heath\LaravelModelCopy\Action;

use Carbon\CarbonImmutable;
use Heath\LaravelModelCopy\Exception\LaravelBatchDeleteModelsValidationException;
use Heath\LaravelModelCopy\Jobs\DeleteModelJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BatchDeleteModels
{
    protected Builder $query;
    protected int $chunkSize = 100;
    protected int $count = 0;
    protected int $limit = 0;
    protected bool $deleteModelsAsJobs = false;
    protected string $queue;
    protected Carbon $processBefore;
    protected int $rpm;
    protected CarbonImmutable $delayingUntil;
    protected CarbonImmutable $startedAt;

    static public function make(): BatchDeleteModels
    {
        return app(BatchDeleteModels::class);
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

    public function deleteModelsAsJobs()
    {
        $this->deleteModelsAsJobs = true;

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
            throw new LaravelBatchDeleteModelsValidationException(
                'Unable to batch delete models as query hasn\'t been defined.'
            );
        }

        if($this->chunkSize <= 1) {
            throw new LaravelBatchDeleteModelsValidationException(
                'Unable to batch delete models as chunk size must be greater than 1. Size: '
                . $this->chunkSize
            );
        }

        if($this->limit < 0) {
            throw new LaravelBatchDeleteModelsValidationException(
                'Unable to batch delete models as limit size must be greater than 0. Size: '
                . $this->limit
            );
        }

        if(isset($this->rpm) && $this->rpm < 0) {
            throw new LaravelBatchDeleteModelsValidationException(
                'Unable to batch delete models as rate per minute (rpm) must be greater than 0. Size: '
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

                $this->deleteModels($items);
            });
    }

    protected function deleteModels(Collection $models)
    {
        $models->each(fn($model) => $this->deleteModel($model));
    }

    protected function deleteModel(Model $model)
    {
        $this->incrementCount();

        if($this->hasReachedLimit()) {
            return false;
        }

        if($this->delayHasExceededProcessBefore()) {
            return false;
        }

        $deleteModel = DeleteModel::make()
            ->delete($model)
            ->when(isset($this->processBefore), fn($self) => $self->processBefore($this->processBefore));

        $this->deleteModelsAsJobs ? $this->deleteAsJobs($deleteModel) : $this->deleteSync($deleteModel);
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

    protected function deleteAsJobs(DeleteModel $deleteModel)
    {
        $job = DeleteModelJob::dispatch($deleteModel);

        if(isset($this->rpm)) {
            $job->delay($this->delayUntil());
        }

        if(isset($this->queue)) {
            $job->onQueue($this->queue);
        }
    }

    protected function deleteSync(DeleteModel $deleteModel)
    {
        $deleteModel->run();
    }

    protected function delayUntil()
    {
        $this->delayingUntil = $this->startedAt->addSeconds(floor(60 / $this->rpm * $this->count));

        return $this->delayingUntil;
    }
}