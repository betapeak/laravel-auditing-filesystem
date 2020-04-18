<?php

namespace BetaPeak\Auditing\Drivers\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Person extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public function toAudit(): array
    {
        return [
            'old_values' => [
                'name' => 'John',
                'age' => 31
            ],
            'new_values' => [
                'name' => 'John',
                'age' => 32
            ]
        ];
    }
}
