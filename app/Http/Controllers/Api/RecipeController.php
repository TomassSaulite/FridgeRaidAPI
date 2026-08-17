<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecipeSuggestionsRequest;
use App\Models\Recipe;
use App\Services\SubstitutionFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function __construct(private SubstitutionFinder $substitutionFinder) {}

    public function suggestions(RecipeSuggestionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $pantryIngredientIds = $request->user()->pantryItems()->pluck('ingredient_id');
        $expiringIngredientIds = $request->user()->pantryItems()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays((int) ($validated['expires_within'] ?? 3)))
            ->pluck('ingredient_id');

        $recipes = Recipe::query()
            ->with(['ingredients.ingredient', 'tags'])
            ->when($validated['ease'] ?? $validated['ease_tier'] ?? null, fn ($query, $tier) => $query->where('ease_tier', $tier))
            ->when($validated['tag'] ?? null, fn ($query, $tag) => $query->whereHas('tags', fn ($tags) => $tags->where('tag', $tag)))
            ->get()
            ->map(function (Recipe $recipe) use ($pantryIngredientIds, $expiringIngredientIds): array {
                $required = $recipe->ingredients->where('optional', false);
                $matched = $required->whereIn('ingredient_id', $pantryIngredientIds);
                $missing = $required->whereNotIn('ingredient_id', $pantryIngredientIds);
                $totalRequired = $required->count();
                $matchScore = $totalRequired > 0 ? $matched->count() / $totalRequired : 0;
                $hasExpiringMatch = $matched->pluck('ingredient_id')->intersect($expiringIngredientIds)->isNotEmpty();
                $missingWithSubstitutes = $missing->map(function ($ingredient) use ($pantryIngredientIds): array {
                    return [
                        'id' => $ingredient->ingredient_id,
                        'name' => $ingredient->ingredient->name,
                        'substitute' => $this->substitutionFinder->findSubstitute($ingredient->ingredient_id, $pantryIngredientIds),
                    ];
                })->values();
                $substituteCredits = $missingWithSubstitutes->sum(
                    fn (array $ingredient): float => ($ingredient['substitute']['confidence'] ?? 0) >= 0.6 ? 0.75 : 0
                );
                $smartMatchScore = $totalRequired > 0 ? ($matched->count() + $substituteCredits) / $totalRequired : 0;

                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'ease_tier' => $recipe->ease_tier,
                    'prep_minutes' => $recipe->prep_minutes,
                    'cook_minutes' => $recipe->cook_minutes,
                    'servings' => $recipe->servings,
                    'match_score' => round($matchScore, 2),
                    'smart_match_score' => round($smartMatchScore, 2),
                    'has_expiring_match' => $hasExpiringMatch,
                    'required_ingredients' => $required->map(fn ($ingredient) => [
                        'id' => $ingredient->ingredient_id,
                        'name' => $ingredient->ingredient->name,
                        'have_it' => $pantryIngredientIds->contains($ingredient->ingredient_id),
                        'expiring_soon' => $expiringIngredientIds->contains($ingredient->ingredient_id),
                    ])->values(),
                    'missing_ingredients' => $missingWithSubstitutes,
                ];
            })
            ->filter(fn (array $recipe): bool => $recipe['smart_match_score'] > 0)
            ->sort(function (array $a, array $b): int {
                if ($a['has_expiring_match'] !== $b['has_expiring_match']) {
                    return $b['has_expiring_match'] <=> $a['has_expiring_match'];
                }

                if ($a['smart_match_score'] !== $b['smart_match_score']) {
                    return $b['smart_match_score'] <=> $a['smart_match_score'];
                }

                if ($a['match_score'] !== $b['match_score']) {
                    return $b['match_score'] <=> $a['match_score'];
                }

                return count($a['missing_ingredients']) <=> count($b['missing_ingredients']);
            })
            ->values()
            ->when(isset($validated['limit']), fn ($recipes) => $recipes->take($validated['limit']))
            ->values();

        return response()->json($recipes);
    }

    public function show(Request $request, Recipe $recipe): JsonResponse
    {
        $pantryIngredientIds = $request->user()->pantryItems()->pluck('ingredient_id');
        $recipe->load('ingredients.ingredient');

        return response()->json([
            'id' => $recipe->id,
            'title' => $recipe->title,
            'description' => $recipe->description,
            'instructions' => $recipe->instructions,
            'ease_tier' => $recipe->ease_tier,
            'prep_minutes' => $recipe->prep_minutes,
            'cook_minutes' => $recipe->cook_minutes,
            'servings' => $recipe->servings,
            'isFavorite' => $request->user()
                ->favoriteRecipes()
                ->where('recipe_id', $recipe->id)
                ->exists(),
            'ingredients' => $recipe->ingredients->map(function ($ingredient) use ($pantryIngredientIds): array {
                $hasIngredient = $pantryIngredientIds->contains($ingredient->ingredient_id);

                return [
                    'name' => $ingredient->ingredient->name,
                    'quantity' => $ingredient->quantity,
                    'unit' => $ingredient->unit,
                    'optional' => $ingredient->optional,
                    'have_it' => $hasIngredient,
                    'substitute' => $hasIngredient
                        ? null
                        : $this->substitutionFinder->findSubstitute($ingredient->ingredient_id, $pantryIngredientIds),
                ];
            })->values(),
        ]);
    }
}
