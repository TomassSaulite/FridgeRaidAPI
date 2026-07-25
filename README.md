# Fridge to Dinner — API

A Laravel API that turns "what's actually in my fridge" into ranked, realistic dinner suggestions — and knows when a missing ingredient has a workable substitute already on your shelf.

## What makes this different from a typical recipe-matcher

Most pantry-matching demos do a flat "ingredients you have ∩ ingredients the recipe needs" check. This one goes a step further: ingredients are modeled as a graph, connected by substitution edges with a confidence weight (butter → margarine is a strong substitute; white wine → chicken stock, weaker). When a recipe is missing something, a depth-limited BFS checks whether a usable substitute is already in your pantry — so a recipe calling for buttermilk doesn't get unfairly ranked below one you're equally equipped to cook, just because you have milk and a splash of lemon juice instead.

## Stack

- Laravel 11, PHP 8.3
- MySQL
- Laravel Sanctum (token auth — built this way from day one so the same API can back a mobile client later without an auth rewrite)
- PHPUnit feature tests, run in CI on every push

## Core features

- **Pantry tracking** — add ingredients by free-text name; resolved against a canonical ingredient list plus an alias table, so "tomatoes," "cherry tomato," and "roma tomato" all map to one underlying ingredient.
- **Ranked suggestions** — recipes scored by how much of what they need you actually have, with a boost for recipes that use something close to expiring.
- **Substitution-aware matching** — a graph of ingredient substitutions with weighted confidence, traversed via BFS, surfaces "you can basically make this" recipes that a naive exact-match system would rank as incomplete.
- **Recipe detail** — full ingredient list with per-item have/missing/substitutable state.

## API overview

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/register` | Create an account, returns a bearer token |
| POST | `/api/login` | Authenticate, returns a bearer token |
| POST | `/api/logout` | Revoke the current token |
| GET | `/api/pantry` | List the authenticated user's pantry |
| POST | `/api/pantry` | Add an ingredient to the pantry |
| DELETE | `/api/pantry/{id}` | Remove a pantry item |
| GET | `/api/ingredients/search?q=` | Autocomplete search over ingredients + aliases |
| GET | `/api/ingredients/{id}/substitutes` | List known substitutes for an ingredient |
| GET | `/api/recipes/suggestions` | Ranked recipe suggestions for the current pantry |
| GET | `/api/recipes/{id}` | Full recipe detail with have/missing/substitute flags |

All routes except register/login require a `Authorization: Bearer <token>` header.

## Running locally

```bash
composer install
cp .env.example .env
php artisan key:generate
# set DB credentials in .env, then:
php artisan migrate
php artisan db:seed --class=RecipeImportSeeder
php artisan serve
```

## Testing

```bash
php artisan test
```

CI runs the full suite against a MySQL service container on every push — see `.github/workflows/tests.yml`.

## Why token auth instead of Sanctum's SPA/cookie mode

The frontend is a separate Vue SPA, and the same auth needs to work for a possible future mobile client without a redesign — so every client (web or native) authenticates identically via a bearer token rather than relying on same-origin cookies.

## Related repo

The frontend lives at [link to your Vue repo] — a Vue 3 SPA consuming this API.
