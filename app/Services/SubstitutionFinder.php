<?php

namespace App\Services;

use App\Models\IngredientSubstitution;
use Illuminate\Support\Collection;

class SubstitutionFinder
{
    /**
     * @param  Collection<int, int>  $pantryIngredientIds
     * @return array{ingredient_id: int, confidence: float, path: list<string>}|null
     */
    public function findSubstitute(int $ingredientId, Collection $pantryIngredientIds, int $maxDepth = 2): ?array
    {
        $pantryIds = $pantryIngredientIds->flip();
        $visited = [$ingredientId => true];
        $frontier = [$ingredientId => ['confidence' => 1.0, 'path' => []]];

        for ($depth = 0; $depth < $maxDepth && $frontier !== []; $depth++) {
            $edges = IngredientSubstitution::query()
                ->with('substitute:id,name')
                ->whereIn('ingredient_id', array_keys($frontier))
                ->get()
                ->groupBy('ingredient_id');
            $nextFrontier = [];
            $candidates = collect();

            foreach ($frontier as $currentId => $state) {
                foreach ($edges->get($currentId, collect()) as $edge) {
                    $substituteId = $edge->substitute_ingredient_id;

                    if (isset($visited[$substituteId])) {
                        continue;
                    }

                    $visited[$substituteId] = true;
                    $confidence = $state['confidence'] * $edge->confidence;
                    $path = [...$state['path'], $edge->substitute->name];

                    if (isset($pantryIds[$substituteId])) {
                        $candidates->push([
                            'ingredient_id' => $substituteId,
                            'confidence' => round($confidence, 2),
                            'path' => $path,
                        ]);

                        continue;
                    }

                    $nextFrontier[$substituteId] = ['confidence' => $confidence, 'path' => $path];
                }
            }

            if ($candidates->isNotEmpty()) {
                return $candidates->sortByDesc('confidence')->first();
            }

            $frontier = $nextFrontier;
        }

        return null;
    }
}
