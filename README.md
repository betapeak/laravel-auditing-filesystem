This driver provides the ability to save your model audits in CSV files. It's integrated with Laravel's Storage system
so you can use any of the disks specified in your application as the storage destinations of the audit files.

We also recognize that many systems generate a substantial amount of audit actions and that's why the package allows
to specify how the audit files are generated - from a flat catch-all file to files generated for each hour of operation.

### Installation

This driver requires that you are using `owen-it/laravel-auditing: ^4.1`. Provided this is fulfilled,
you can install the driver like so:

```
composer require betapeak/laravel-auditing-filesystem
```

### Setup

You need to add the following config entries in config/audit.php if you need to change the default behaviour of the driver.
The `drivers` key of the config file should look like so:

```
    ...
    'drivers' => [
        'database' => [
            'table'      => 'audits',
            'connection' => null,
        ],
        'filesystem' => [
            'disk'         => 'local',
            'dir'          => 'audit/',
            'filename'     => 'audit.csv',
            'logging_type' => 'single',
        ],
    ],
    ...
```

For simplicity, there are just 4 settings you can adjust and they're described below:

| Parameter   |      Type      |  Description |
|----------|:-------------:|------|
| disk | (string) | The name of any filesystem disk in the app. Usage of remote disks (AWS, Rackspace, etc) is discouraged, as it introduces substantial additional http request overheads to the remote disk |
| dir | (string) | The directory on the disk where the audit csv files will be saved |
| filename | (string) | The filename of the audit file. If logging_type is different from 'single', this filename is ignored as it's being dynamically generated |
| logging_type | (string) | Defines how the audit files are being generated. One of 'single', 'daily' or 'hourly'. |


### Usage

You can use the driver in any Auditable model like so:

```
<?php
namespace App\Models;

use BetaPeak\Auditing\Drivers\FilesystemDriver;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SomeModel extends Model implements AuditableContract
{
    use Auditable;

    /**
     * Filesystem Audit Driver
     *
     * @var BetaPeak\Auditing\Drivers\Filesystem
     */
    protected $auditDriver = FilesystemDriver::class;

    // ...
}
```

More information on using customer drivers with owen-it/laravel-auditing can be found on their [homepage](http://laravel-auditing.com/docs/4.1/audit-drivers)