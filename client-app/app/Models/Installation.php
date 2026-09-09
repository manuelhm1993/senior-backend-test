<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'installation_id', 'pairing_secret', 'status', 
    'workspace', 'facility', 'owner_info', 
    'devices', 'contract_version', 'settings',
])]
class Installation extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner_info' => 'array',
            'devices'    => 'array',
            'settings'   => 'array',
        ];
    }
}
