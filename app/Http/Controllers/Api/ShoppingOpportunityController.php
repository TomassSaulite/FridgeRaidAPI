<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShoppingOpportunitiesRequest;
use App\Services\ShoppingOpportunityFinder;
use Illuminate\Http\JsonResponse;

class ShoppingOpportunityController extends Controller
{
    public function __construct(private ShoppingOpportunityFinder $shoppingOpportunityFinder) {}

    public function index(ShoppingOpportunitiesRequest $request): JsonResponse
    {
        return response()->json(
            $this->shoppingOpportunityFinder->findFor(
                $request->user(),
                $request->integer('limit', 5),
            )
        );
    }
}
