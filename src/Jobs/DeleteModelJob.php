<?php

namespace Heath\LaravelModelCopy\Jobs;

use Heath\LaravelModelCopy\Action\DeleteModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected DeleteModel $deleteModel;

    public function __construct(DeleteModel $deleteModel)
    {
        $this->deleteModel = $deleteModel;
    }

    public function handle()
    {
        $this->deleteModel->run();
    }

    public function getDeleteModel()
    {
        return $this->deleteModel;
    }
}
