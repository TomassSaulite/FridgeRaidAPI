<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'ease_tier' => $this->ease_tier,
            'prep_minutes' => $this->prep_minutes,
            'cook_minutes' => $this->cook_minutes,
            'servings' => $this->servings,
            'instructions' => $this->when(array_key_exists('include_instructions', $attributes), $this->instructions),
            'source_url' => $this->source_url,
            'match_score' => $this->when(array_key_exists('match_score', $attributes), (float) $this->match_score),
            'total_required' => $this->when(array_key_exists('total_required', $attributes), (int) $this->total_required),
            'matched' => $this->when(array_key_exists('matched', $attributes), (int) $this->matched),
            'missing' => $this->when(array_key_exists('missing', $attributes), (int) $this->missing),
            'expiry_boosted' => $this->when(array_key_exists('expires_soon', $attributes), (bool) $this->expires_soon),
            'missing_ingredients' => IngredientResource::collection($this->whenLoaded('missingIngredients')),
            'ingredients' => $this->whenLoaded('ingredientModels', function (): array {
                return $this->ingredientModels->map(fn ($ingredient): array => [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'category' => $ingredient->category,
                    'quantity' => $ingredient->pivot->quantity,
                    'unit' => $ingredient->pivot->unit,
                    'optional' => (bool) $ingredient->pivot->optional,
                    'have_it' => (bool) ($ingredient->have_it ?? false),
                ])->all();
            }),
            'tags' => $this->whenLoaded('tags', fn (): array => $this->tags->pluck('tag')->values()->all()),
        ];
    }
}
