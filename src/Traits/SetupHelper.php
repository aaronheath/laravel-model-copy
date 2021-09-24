<?php

namespace Heath\LaravelModelCopy\Traits;

use Heath\LaravelModelCopy\Action\CopyModel;
use Illuminate\Database\Eloquent\Model;

trait SetupHelper
{
    protected Model $fromModel;
    protected string $toModel;
    protected bool $deleteOriginal = false;

    static public function make(): CopyModel
    {
        return app(CopyModel::class);
    }

    public function copy(Model $fromModel)
    {
        $this->fromModel = $fromModel;

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
}