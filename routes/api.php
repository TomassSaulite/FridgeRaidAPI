<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\PantryController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\FavoriteRecipesController;
use App\Http\Controllers\Api\ShoppingOpportunityController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/pantry', [PantryController::class, 'index']);
    Route::get('/pantry/one-shop-away', [ShoppingOpportunityController::class, 'index']);
    Route::post('/pantry', [PantryController::class, 'store']);
    Route::delete('/pantry/{pantryItem}', [PantryController::class, 'destroy']);
    Route::get('/ingredients/search', [IngredientController::class, 'search']);
    Route::get('/ingredients/{ingredient}/substitutes', [IngredientController::class, 'substitutes']);

    Route::get('/recipes/suggestions', [RecipeController::class, 'suggestions']);
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);

    Route::post('/recipes/{recipe}/favorite', [FavoriteRecipesController::class, 'favorite']);
    Route::delete('/recipes/{recipe}/favorite', [FavoriteRecipesController::class, 'unfavorite']);
    Route::get('/favorites', [FavoriteRecipesController::class, 'index']);

});
