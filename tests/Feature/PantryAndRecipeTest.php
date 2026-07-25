<?php

use App\Models\Ingredient;
use App\Models\IngredientSubstitution;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('adds, lists, searches, and removes pantry items', function (): void {
    $user = User::factory()->create();
    $tomato = Ingredient::create(['name' => 'tomato', 'category' => 'produce']);
    $tomato->aliases()->create(['alias' => 'tomatoes']);
    Sanctum::actingAs($user);

    $this->postJson('/api/pantry', [
        'ingredient_name' => 'tomatoes',
        'quantity' => '4',
        'expires_at' => now()->addDays(2)->toDateString(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.ingredient.id', $tomato->id);

    $this->getJson('/api/pantry')
        ->assertOk()
        ->assertJsonPath('data.0.ingredient.name', 'tomato');

    $this->getJson('/api/ingredients/search?q=tom')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'tomato');

    $this->deleteJson('/api/pantry/'.$user->pantryItems()->first()->id)
        ->assertOk()
        ->assertJsonPath('message', 'Removed');
});

it('returns ranked recipes with the correct missing ingredients', function (): void {
    $user = User::factory()->create();
    $tomato = Ingredient::create(['name' => 'tomato']);
    $onion = Ingredient::create(['name' => 'onion']);
    $pasta = Ingredient::create(['name' => 'pasta']);
    $basil = Ingredient::create(['name' => 'basil']);

    $pastaRecipe = Recipe::create(['title' => 'Tomato Pasta', 'instructions' => 'Cook it.']);
    $pastaRecipe->ingredients()->createMany([
        ['ingredient_id' => $tomato->id, 'optional' => false],
        ['ingredient_id' => $onion->id, 'optional' => false],
        ['ingredient_id' => $pasta->id, 'optional' => false],
        ['ingredient_id' => $basil->id, 'optional' => true],
    ]);

    $tomatoSoup = Recipe::create(['title' => 'Tomato Soup', 'instructions' => 'Simmer it.']);
    $tomatoSoup->ingredients()->create(['ingredient_id' => $tomato->id, 'optional' => false]);

    $user->pantryItems()->createMany([
        ['ingredient_id' => $tomato->id, 'expires_at' => now()->addDay()],
        ['ingredient_id' => $onion->id],
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/recipes/suggestions')
        ->assertOk()
        ->assertJsonPath('0.title', 'Tomato Soup')
        ->assertJsonPath('1.title', 'Tomato Pasta')
        ->assertJsonPath('1.match_score', 0.67)
        ->assertJsonPath('1.missing_ingredients.0.name', 'pasta');

    $this->getJson('/api/recipes/'.$pastaRecipe->id)
        ->assertOk()
        ->assertJsonPath('instructions', 'Cook it.')
        ->assertJsonPath('ingredients.0.have_it', true)
        ->assertJsonPath('ingredients.2.have_it', false);
});

it('uses substitution graph edges to calculate smart recipe matches', function (): void {
    $user = User::factory()->create();
    $buttermilk = Ingredient::create(['name' => 'buttermilk']);
    $milk = Ingredient::create(['name' => 'milk']);
    $recipe = Recipe::create(['title' => 'Buttermilk Pancakes', 'instructions' => 'Mix and cook.']);
    $recipe->ingredients()->create(['ingredient_id' => $buttermilk->id, 'optional' => false]);
    IngredientSubstitution::create([
        'ingredient_id' => $buttermilk->id,
        'substitute_ingredient_id' => $milk->id,
        'confidence' => 0.7,
        'note' => 'add lemon juice',
    ]);
    $user->pantryItems()->create(['ingredient_id' => $milk->id]);
    Sanctum::actingAs($user);

    $this->getJson('/api/recipes/suggestions')
        ->assertOk()
        ->assertJsonPath('0.match_score', 0)
        ->assertJsonPath('0.smart_match_score', 0.75)
        ->assertJsonPath('0.missing_ingredients.0.substitute.ingredient_id', $milk->id)
        ->assertJsonPath('0.missing_ingredients.0.substitute.confidence', 0.7)
        ->assertJsonPath('0.missing_ingredients.0.substitute.path.0', 'milk');

    $this->getJson('/api/ingredients/'.$buttermilk->id.'/substitutes')
        ->assertOk()
        ->assertJsonPath('0.substitute.name', 'milk');

    $this->getJson('/api/recipes/'.$recipe->id)
        ->assertOk()
        ->assertJsonPath('ingredients.0.have_it', false)
        ->assertJsonPath('ingredients.0.substitute.ingredient_id', $milk->id);
});

it('changes suggestions when the pantry changes', function (): void {
    $user = User::factory()->create();
    $tomato = Ingredient::create(['name' => 'tomato']);
    $onion = Ingredient::create(['name' => 'onion']);
    $tomatoRecipe = Recipe::create(['title' => 'Tomato Salad', 'instructions' => 'Serve.']);
    $tomatoRecipe->ingredients()->create(['ingredient_id' => $tomato->id, 'optional' => false]);
    $onionRecipe = Recipe::create(['title' => 'Onion Soup', 'instructions' => 'Simmer.']);
    $onionRecipe->ingredients()->create(['ingredient_id' => $onion->id, 'optional' => false]);
    $user->pantryItems()->create(['ingredient_id' => $tomato->id]);
    Sanctum::actingAs($user);

    $this->getJson('/api/recipes/suggestions')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $tomatoRecipe->id);

    $user->pantryItems()->delete();
    $user->pantryItems()->create(['ingredient_id' => $onion->id]);

    $this->getJson('/api/recipes/suggestions')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $onionRecipe->id);
});

it('identifies the single purchase that unlocks the most recipes', function (): void {
    $user = User::factory()->create();
    $chicken = Ingredient::create(['name' => 'chicken']);
    $rice = Ingredient::create(['name' => 'rice']);
    $broccoli = Ingredient::create(['name' => 'broccoli']);

    foreach ([
        ['Chicken Rice', $rice],
        ['Chicken Fried Rice', $rice],
        ['Chicken Broccoli', $broccoli],
    ] as [$title, $missingIngredient]) {
        $recipe = Recipe::create(['title' => $title, 'instructions' => 'Cook.']);
        $recipe->ingredients()->createMany([
            ['ingredient_id' => $chicken->id, 'optional' => false],
            ['ingredient_id' => $missingIngredient->id, 'optional' => false],
        ]);
    }

    $user->pantryItems()->create([
        'ingredient_id' => $chicken->id,
        'expires_at' => now()->addDay(),
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/pantry/one-shop-away')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.ingredient.name', 'rice')
        ->assertJsonPath('0.unlock_count', 2)
        ->assertJsonPath('0.expiring_recipe_count', 2)
        ->assertJsonPath('0.recipes.0.title', 'Chicken Rice');
});
