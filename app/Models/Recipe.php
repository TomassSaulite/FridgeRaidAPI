<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'ease_tier', 'prep_minutes', 'cook_minutes', 'servings', 'instructions', 'source_url'])]
class Recipe extends Model
{
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function ingredientModels(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot(['quantity', 'unit', 'optional'])
            ->withTimestamps();
    }

    public function recipeIngredients(): HasMany
    {
        return $this->ingredients();
    }

    public function tags(): HasMany
    {
        return $this->hasMany(RecipeTag::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(
            User::class,
            'favorite_recipes'
        );
    }

}
