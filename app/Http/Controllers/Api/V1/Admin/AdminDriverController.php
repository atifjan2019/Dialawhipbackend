<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class AdminDriverController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::where('role', User::ROLE_DRIVER)->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $tempPassword = Str::password(12);
        $driver = User::create([
            ...$data,
            'password' => $tempPassword,
            'role' => User::ROLE_DRIVER,
        ]);

        return response()->json([
            'data' => [
                'driver' => new UserResource($driver),
                'temp_password' => $tempPassword,
            ],
        ], 201);
    }
}
