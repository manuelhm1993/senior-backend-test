<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'facility_id',
    'installation_id',
    'pairing_secret_hash',
    'status',
    'contract_version',
])]
class Installation extends Model
{
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
