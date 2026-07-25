<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IngredientController extends Controller
{
    public function search(Request $request)
    {
        $query = Str::of((string) $request->query('q', ''))->squish()->lower()->toString();

        if ($query === '') {
            return IngredientResource::collection(collect());
        }

        $ingredients = Ingredient::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhereHas('aliases', fn ($aliases) => $aliases->where('alias', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit(10)
            ->get();

        return IngredientResource::collection($ingredients);
    }

    public function substitutes(Ingredient $ingredient): JsonResponse
    {
        return response()->json(
            $ingredient->substitutions()->with('substitute')->get()
        );
    }
}
