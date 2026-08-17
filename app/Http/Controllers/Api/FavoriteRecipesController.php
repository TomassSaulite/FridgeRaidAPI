<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FavoriteRecipesController extends Controller
{
    public function index(Request $request): JsonResponse
{
    $pantryIngredientIds = $request->user()
        ->pantryItems()
        ->pluck('ingredient_id');

    $recipes = $request->user()
        ->favoriteRecipes()
        ->with(['ingredients.ingredient', 'tags'])
        ->get()
        ->map(function (Recipe $recipe) use ($pantryIngredientIds): array {
            $required = $recipe->ingredients->where('optional', false);

            $matched = $required->whereIn(
                'ingredient_id',
                $pantryIngredientIds
            );

            $missing = $required->whereNotIn(
                'ingredient_id',
                $pantryIngredientIds
            );

            $totalRequired = $required->count();

            $matchScore = $totalRequired > 0
                ? $matched->count() / $totalRequired
                : 0;

            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'ease_tier' => $recipe->ease_tier,
                'prep_minutes' => $recipe->prep_minutes,
                'cook_minutes' => $recipe->cook_minutes,
                'servings' => $recipe->servings,

                'match_score' => round($matchScore, 2),

                'required_ingredients' => $required->map(
                    fn ($ingredient) => [
                        'id' => $ingredient->ingredient_id,
                        'name' => $ingredient->ingredient->name,
                        'have_it' => $pantryIngredientIds->contains(
                            $ingredient->ingredient_id
                        ),
                    ]
                )->values(),

                'missing_ingredients' => $missing->map(
                    fn ($ingredient) => [
                        'id' => $ingredient->ingredient_id,
                        'name' => $ingredient->ingredient->name,
                    ]
                )->values(),
            ];
        });

    return response()->json([
        'favorite_recipes' => $recipes,
    ]);
}

    public function favorite(Request $request, Recipe $recipe)
    {
        $request->user()
            ->favoriteRecipes()
            ->syncWithoutDetaching([$recipe->id]);

        return response()->json([
            'message' => 'Recipe favorited successfully.',
        ]);
    }

    public function unfavorite(Request $request, Recipe $recipe)
    {
        $request->user()
            ->favoriteRecipes()
            ->detach([$recipe->id]);

        return response()->json([
            'message' => 'Recipe unfavorited successfully.',
        ]);
    }
}