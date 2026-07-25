<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category'])]
class Ingredient extends Model
{
    use HasFactory;

    public function aliases(): HasMany
    {
        return $this->hasMany(IngredientAlias::class);
    }

    public function pantryItems(): HasMany
    {
        return $this->hasMany(PantryItem::class);
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
            ->withPivot(['quantity', 'unit', 'optional'])
            ->withTimestamps();
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(IngredientSubstitution::class);
    }
}
