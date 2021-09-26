<?php

namespace Heath\LaravelModelCopy\Console;

use Heath\LaravelModelCopy\Action\CopyModel;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CopyModelCommand extends Command
{
    protected $signature = 'copy-model:copy 
                            {from-model} 
                            {from-model-id} 
                            {to-model} 
                            {--delete} 
                            {--process-before}';

    protected $description = 'Copy an individual model to another table.';

    public function handle()
    {
        $fromModel = $this->argument('from-model');
        $fromModelId = $this->argument('from-model-id');
        $toModel = $this->argument('to-model');
        $deleteOriginal = $this->option('delete');
        $processBefore = $this->option('process-before');

        CopyModel::make()
            ->copy($fromModel::find($fromModelId))
            ->to($toModel)
            ->when($deleteOriginal, fn($self) => $self->deleteOriginal())
            ->when($processBefore, fn($self) => $self->processBefore(Carbon::parse($processBefore)))
            ->run();

        return 0;
    }
}
