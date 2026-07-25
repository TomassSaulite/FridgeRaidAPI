<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RecipeImportSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range('a', 'z') as $letter) {
            $meals = Http::retry(2, 500)
                ->timeout(20)
                ->get('https://www.themealdb.com/api/json/v1/1/search.php', ['f' => $letter])
                ->throw()
                ->json('meals', []);

            foreach ($meals ?? [] as $meal) {
                $this->importMeal($meal);
            }
        }
    }

    private function importMeal(array $meal): void
    {
        $ingredientCount = collect(range(1, 20))
            ->filter(fn (int $position): bool => trim((string) ($meal["strIngredient{$position}"] ?? '')) !== '')
            ->count();

        $recipe = Recipe::updateOrCreate(
            ['source_url' => "https://www.themealdb.com/meal/{$meal['idMeal']}"],
            [
                'title' => $meal['strMeal'],
                'description' => $meal['strCategory'] ?? null,
                'ease_tier' => $this->resolveEaseTier($ingredientCount),
                'instructions' => $meal['strInstructions'] ?? '',
            ],
        );

        $recipe->recipeIngredients()->delete();
        $recipe->tags()->delete();

        foreach (array_filter([$meal['strCategory'] ?? null, $meal['strArea'] ?? null]) as $tag) {
            $recipe->tags()->create(['tag' => Str::of($tag)->lower()->trim()->toString()]);
        }

        for ($position = 1; $position <= 20; $position++) {
            $rawIngredient = trim((string) ($meal["strIngredient{$position}"] ?? ''));

            if ($rawIngredient === '') {
                continue;
            }

            $alias = Str::of($rawIngredient)->squish()->lower()->toString();
            $canonical = Str::singular($alias);
            $ingredient = Ingredient::firstOrCreate(['name' => $canonical]);

            if ($alias !== $canonical) {
                $ingredient->aliases()->firstOrCreate(['alias' => $alias]);
            }

            [$quantity, $unit] = $this->splitMeasure((string) ($meal["strMeasure{$position}"] ?? ''));
            $recipe->recipeIngredients()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit' => $unit,
            ]);
        }
    }

    private function splitMeasure(string $measure): array
    {
        $measure = trim(preg_replace('/\s+/', ' ', $measure));

        if ($measure === '') {
            return [null, null];
        }

        preg_match('/^([\d¼½¾⅓⅔.\/\s]+)\s*(.*)$/u', $measure, $parts);

        return [
            isset($parts[1]) ? trim($parts[1]) : null,
            isset($parts[2]) && trim($parts[2]) !== '' ? trim($parts[2]) : null,
        ];
    }

    private function resolveEaseTier(int $ingredientCount): string
    {
        return match (true) {
            $ingredientCount <= 5 => 'lazy',
            $ingredientCount <= 10 => 'normal',
            default => 'ambitious',
        };
    }
}
