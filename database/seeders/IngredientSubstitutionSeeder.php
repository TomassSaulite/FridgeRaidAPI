<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientSubstitution;
use Illuminate\Database\Seeder;

class IngredientSubstitutionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['butter', 'margarine', 0.9, '1:1'],
            ['margarine', 'butter', 0.9, '1:1'],
            ['buttermilk', 'milk', 0.7, 'add 1 tbsp lemon juice per cup'],
            ['egg', 'applesauce', 0.6, '1/4 cup per egg, baking only'],
            ['heavy cream', 'evaporated milk', 0.7, '1:1'],
            ['fresh basil', 'dried basil', 0.6, 'use 1/3 the amount'],
            ['white wine', 'chicken stock', 0.5, 'splash of vinegar to taste'],
            ['sour cream', 'greek yogurt', 0.85, '1:1'],
            ['greek yogurt', 'sour cream', 0.85, '1:1'],
        ] as [$ingredientName, $substituteName, $confidence, $note]) {
            $ingredient = Ingredient::firstOrCreate(['name' => $ingredientName]);
            $substitute = Ingredient::firstOrCreate(['name' => $substituteName]);

            IngredientSubstitution::updateOrCreate(
                [
                    'ingredient_id' => $ingredient->id,
                    'substitute_ingredient_id' => $substitute->id,
                ],
                ['confidence' => $confidence, 'note' => $note],
            );
        }
    }
}
