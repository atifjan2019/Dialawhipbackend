<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\IdVerificationResource;
use App\Models\IdVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdVerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $verifications = IdVerification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => IdVerificationResource::collection($verifications),
            'user_status' => $user->verification_status,
            'verified_at' => $user->verified_at,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doc_type' => ['required', 'string', 'in:' . implode(',', IdVerification::DOC_TYPES)],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:8192'], // 8MB
        ]);

        $user = $request->user();

        // Block if already verified or pending
        if ($user->verification_status === User::VERIFY_PENDING) {
            return response()->json([
                'message' => 'An ID upload is already pending review.',
            ], 409);
        }

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $filename = Str::ulid()->toString() . '.' . $ext;
        $path = $file->storeAs("id-verifications/{$user->id}", $filename, 'local');

        $verification = IdVerification::create([
            'user_id' => $user->id,
            'doc_type' => $validated['doc_type'],
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'status' => IdVerification::STATUS_PENDING,
        ]);

        $user->update(['verification_status' => User::VERIFY_PENDING]);

        return response()->json([
            'data' => new IdVerificationResource($verification),
        ], 201);
    }
}
