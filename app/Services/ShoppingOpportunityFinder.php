<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Collection;

class ShoppingOpportunityFinder
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function findFor(User $user, int $limit = 5): Collection
    {
        $pantryIngredientIds = $user->pantryItems()->pluck('ingredient_id');
        $expiringIngredientIds = $user->pantryItems()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(3))
            ->pluck('ingredient_id');
        $opportunities = collect();

        Recipe::query()
            ->with('ingredients.ingredient')
            ->get()
            ->each(function (Recipe $recipe) use ($pantryIngredientIds, $expiringIngredientIds, $opportunities): void {
                $required = $recipe->ingredients->where('optional', false);
                $missing = $required->whereNotIn('ingredient_id', $pantryIngredientIds);

                if ($required->isEmpty() || $missing->count() !== 1) {
                    return;
                }

                $missingIngredient = $missing->first();
                $matched = $required->whereIn('ingredient_id', $pantryIngredientIds);
                $hasExpiringMatch = $matched->pluck('ingredient_id')->intersect($expiringIngredientIds)->isNotEmpty();
                $ingredientId = $missingIngredient->ingredient_id;
                $opportunity = $opportunities->get($ingredientId, [
                    'ingredient' => [
                        'id' => $ingredientId,
                        'name' => $missingIngredient->ingredient->name,
                        'category' => $missingIngredient->ingredient->category,
                    ],
                    'priority_score' => 0.0,
                    'expiring_recipe_count' => 0,
                    'recipes' => [],
                ]);

                $opportunity['priority_score'] += $hasExpiringMatch ? 1.5 : 1.0;
                $opportunity['expiring_recipe_count'] += (int) $hasExpiringMatch;
                $opportunity['recipes'][] = [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'ease_tier' => $recipe->ease_tier,
                    'match_score' => round($matched->count() / $required->count(), 2),
                    'has_expiring_match' => $hasExpiringMatch,
                ];
                $opportunities->put($ingredientId, $opportunity);
            });

        return $opportunities
            ->values()
            ->map(function (array $opportunity): array {
                $opportunity['unlock_count'] = count($opportunity['recipes']);
                $opportunity['priority_score'] = round($opportunity['priority_score'], 2);
                $opportunity['recipes'] = collect($opportunity['recipes'])
                    ->sortByDesc('has_expiring_match')
                    ->values()
                    ->all();

                return $opportunity;
            })
            ->sortByDesc('priority_score')
            ->take($limit)
            ->values();
    }
}
