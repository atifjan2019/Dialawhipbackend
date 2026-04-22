<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminCustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::where('role', User::ROLE_CUSTOMER)
            ->withCount('orders')
            ->withSum('orders as lifetime_pence', 'total_pence');

        if ($search = $request->query('filter.search')) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $search).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }

        return UserResource::collection(
            $query->orderByDesc('created_at')->paginate(min((int) $request->query('limit', 25), 100))
        );
    }

    public function show(User $customer): JsonResponse
    {
        abort_unless($customer->isCustomer(), 404);

        return response()->json([
            'data' => [
                ...((new UserResource($customer))->toArray(request())),
                'orders' => $customer->orders()->orderByDesc('created_at')->limit(50)->get(),
                'addresses' => $customer->addresses,
            ],
        ]);
    }
}
