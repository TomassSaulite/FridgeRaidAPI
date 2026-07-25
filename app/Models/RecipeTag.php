<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['recipe_id', 'tag'])]
class RecipeTag extends Model
{
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
