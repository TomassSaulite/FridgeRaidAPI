<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PantryStoreRequest;
use App\Http\Resources\PantryItemResource;
use App\Models\Ingredient;
use App\Models\PantryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PantryController extends Controller
{
    public function index(Request $request)
    {
        return PantryItemResource::collection(
            $request->user()->pantryItems()->with('ingredient')->latest()->get()
        );
    }

    public function store(PantryStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ingredient = isset($validated['ingredient_id'])
            ? Ingredient::findOrFail($validated['ingredient_id'])
            : $this->resolveIngredient($validated['ingredient_name']);

        $pantryItem = PantryItem::updateOrCreate(
            ['user_id' => $request->user()->id, 'ingredient_id' => $ingredient->id],
            [
                'quantity' => $validated['quantity'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
            ],
        );

        return PantryItemResource::make($pantryItem->load('ingredient'))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, PantryItem $pantryItem): JsonResponse
    {
        abort_unless($pantryItem->user_id === $request->user()->id, 403);

        $pantryItem->delete();

        return response()->json(['message' => 'Removed']);
    }

    private function resolveIngredient(string $name): Ingredient
    {
        $normalized = Str::of($name)->squish()->lower()->toString();

        $ingredient = Ingredient::query()
            ->where('name', $normalized)
            ->orWhereHas('aliases', fn ($query) => $query->where('alias', $normalized))
            ->first();

        return $ingredient ?? Ingredient::create(['name' => $normalized]);
    }
}
