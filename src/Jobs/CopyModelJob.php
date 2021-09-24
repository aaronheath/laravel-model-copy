<?php

namespace Heath\LaravelModelCopy\Jobs;

use Heath\LaravelModelCopy\Action\CopyModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CopyModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected CopyModel $copyModel;

    public function __construct(CopyModel $copyModel)
    {
        $this->copyModel = $copyModel;
    }

    public function handle()
    {
        $this->copyModel->run();
    }

    public function getCopyModel()
    {
        return $this->copyModel;
    }
}
