# Laravel Model Copy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aaronheath/laravel-model-copy.svg?style=flat-square)](https://packagist.org/packages/aaronheath/laravel-model-copy)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/aaronheath/laravel-model-copy/run-tests?label=tests)](https://github.com/aaronheath/laravel-model-copy/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/daronheath/laravel-model-copy.svg?style=flat-square)](https://packagist.org/packages/aaronheath/laravel-model-copy)

Laravel model copy helps you copy, move records from one database table to another. This solution is designed for moving un-needed records to another table in a safe manner which can then be backed-up and, if need, truncated.

The package is also able to assist in performing batch model deletions over using the same conditions as are made available when copying models.

## Installation

You can install the package via composer:

```bash
composer require aaronheath/laravel-model-copy
```

## Usage

The package can be used in one of three ways:

- A) Copy or move an individual model.
- B) Batch copy or move many models from a query.
- C) Batch deletion of models from a query.

### Copy or move individual model

Copying an individual model is as easy as...

```php
<?php

use Heath\LaravelModelCopy\Action\CopyModel;
use App\Models\ExampleA;
use App\Models\ExampleB;

CopyModel::make()
    ->copy(Example::find(1))
    ->to(ExampleB::class)
    ->run();
```

Copying an individual model and deleting the original record is a easy as including `->deleteOriginal()`.

```php
<?php

use Heath\LaravelModelCopy\Action\CopyModel;
use App\Models\ExampleA;
use App\Models\ExampleB;

CopyModel::make()
    ->copy(Example::find(1))
    ->to(ExampleB::class)
    ->deleteOriginal()
    ->run();
```

We may not want to copy or move the model if it's after a certain time. This can be achieved by including `->processBefore(now()->addHour())`

```php
<?php

use Heath\LaravelModelCopy\Action\CopyModel;
use App\Models\ExampleA;
use App\Models\ExampleB;

CopyModel::make()
    ->copy(Example::find(1))
    ->to(ExampleB::class)
    ->processBefore(now()->addHour())
    ->run();
```

### Batch copy or move many models from a query

Most likely you won't be wanting to move just one record, this is where batch copying or moving comes into play.

Here's a simple batch copy which will make a copy of all model ExampleA records where they were `handled_at` more than three years ago. 

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->run();
```

If we want to instead delete the original model, all we need to do is include `->deleteOriginal()`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->deleteOriginal()
    ->run();
```

If we want limit on how many records we want to copy / move in one go, we can use `->limit(1000)`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->limit(1000)
    ->run();
```

By default queries will be chunked into 100 record batches. If you wish to use your own chunking value, this can be achieved by `->chunk(500)`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->chunk(500)
    ->run();
```

Up until now, all actions happen in one syncronious stream. In real world situation it's better to process individual copy / move model actions by pushing them to the queue. This can be achieved by including `->copyModelsAsJobs()`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->copyModelsAsJobs()
    ->run();
```

If a queue besides the default wants to be used, then include `->onQueue('queue-name')`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->copyModelsAsJobs()
    ->onQueue('queue-name')
    ->run();
```

When moving large sets of data it may take quite some time. In these cases you may want to group your moving batches into blocks of time. Let's say we want to run the script nightly at 23:00 and want to make sure we stop moving copying models at 05:00 the next day. This can be achieved by using `->processBefore(now()->addDay()->setTime(5, 0, 0)`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->copyModelsAsJobs()
    ->processBefore(now()->addDay()->setTime(5, 0, 0)) // 05:00 tomorrow
    ->run();
```

We may also want to limit how many records are copied / moved in any given minute. To do this a rate per minute (rpm) can be defined. If we're wanting to ensure that we only copy 20 records per minute then we can use `->rpm(20)`. Using this feature requires `->copyModelsAsJobs()` to be used.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->copyModelsAsJobs()
    ->rpm(20)
    ->run();
```

### Real world example

Let's say we want to move all records that were `handled_at` over three years ago to another table. We want to only have the run until 06:00 the next day. We also want to limit load on the database, so we'll only move 100 records per minute. To achieve this we'd use the following...

```php
<?php

use Heath\LaravelModelCopy\Action\BatchCopyModels;
use App\Models\ExampleA;
use App\Models\ExampleB;

BatchCopyModels::make()
    ->to(ExampleB::class)
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->copyModelsAsJobs()
    ->processBefore(now()->addDay()->setTime(6, 0, 0)) // 06:00 tomorrow
    ->rpm(100)
    ->run();
```

### Delete individual model

Deleting an individual model is as easy as...

```php
<?php

use Heath\LaravelModelCopy\Action\DeleteModel;
use App\Models\ExampleA;

DeleteModel::make()
    ->delete(ExampleA::find(1))
    ->run();
```

### Batch deleting models from a query

Most likely you won't be wanting to delete just one record, this is where batch deleting comes into play.

Here's a simple batch delete which will delete all model ExampleA records where they were `handled_at` more than three years ago.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use App\Models\ExampleA;

BatchDeleteModels::make()
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->run();
```

If we want limit on how many records we want to delete in one go, we can use `->limit(1000)`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use App\Models\ExampleA;

BatchDeleteModels::make()
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->limit(1000)
    ->run();
```

By default queries will be chunked into 100 record batches. If you wish to use your own chunking value, this can be achieved by `->chunk(500)`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use App\Models\ExampleA;

BatchDeleteModels::make()
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->chunk(500)
    ->run();
```

Up until now, all actions happen in one syncronious stream. In real world situation it's better to process individual model deletion actions by pushing them to the queue. This can be achieved by including `->copyModelsAsJobs()`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use App\Models\ExampleA;

BatchDeleteModels::make()
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->deleteModelsAsJobs()
    ->run();
```

If a queue besides the default wants to be used, then include `->onQueue('queue-name')`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use App\Models\ExampleA;

BatchDeleteModels::make()
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->deleteModelsAsJobs()
    ->onQueue('queue-name')
    ->run();
```

When deleting large sets of data it may take quite some time. In these cases you may want to group your deleting batches into blocks of time. Let's say we want to run the script nightly at 23:00 and want to make sure we stop moving deleting models at 05:00 the next day. This can be achieved by using `->processBefore(now()->addDay()->setTime(5, 0, 0)`.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use App\Models\ExampleA;

BatchCopyModels::make()
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->deleteModelsAsJobs()
    ->processBefore(now()->addDay()->setTime(5, 0, 0)) // 05:00 tomorrow
    ->run();
```

We may also want to limit how many records are deleted in any given minute. To do this a rate per minute (rpm) can be defined. If we're wanting to ensure that we only delete 20 records per minute then we can use `->rpm(20)`. Using this feature requires `->copyModelsAsJobs()` to be used.

```php
<?php

use Heath\LaravelModelCopy\Action\BatchDeleteModels;
use App\Models\ExampleA;

BatchDeleteModels::make()
    ->query(
        ExampleA::where('handled_at', '<', now()->subYears(3))
    )
    ->deleteModelsAsJobs()
    ->rpm(20)
    ->run();
```

## Testing

```bash
composer test
```

## Credits

- [Aaron Heath](https://github.com/aaronheath)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
