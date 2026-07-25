<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PantryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'expires_at' => $this->expires_at?->toDateString(),
            'ingredient' => IngredientResource::make($this->whenLoaded('ingredient')),
        ];
    }
}
