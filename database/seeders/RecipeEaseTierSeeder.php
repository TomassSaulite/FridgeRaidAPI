<?php

namespace Database\Seeders;

use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeEaseTierSeeder extends Seeder
{
    public function run(): void
    {
        Recipe::query()
            ->withCount('ingredients')
            ->lazyById()
            ->each(function (Recipe $recipe): void {
                $recipe->update([
                    'ease_tier' => match (true) {
                        $recipe->ingredients_count <= 5 => 'lazy',
                        $recipe->ingredients_count <= 10 => 'normal',
                        default => 'ambitious',
                    },
                ]);
            });
    }
}
