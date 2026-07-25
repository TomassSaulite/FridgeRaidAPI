<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ingredient_id', 'substitute_ingredient_id', 'confidence', 'note'])]
class IngredientSubstitution extends Model
{
    protected function casts(): array
    {
        return ['confidence' => 'float'];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'substitute_ingredient_id');
    }
}
