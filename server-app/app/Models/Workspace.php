<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Workspace extends Model
{
    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }
}
