<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\IdVerificationResource;
use App\Models\IdVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('filter.status', 'pending');
        $search = $request->query('filter.search');

        $query = IdVerification::query()
            ->with(['user', 'reviewer'])
            ->orderByDesc('created_at');

        if (in_array($status, [IdVerification::STATUS_PENDING, IdVerification::STATUS_APPROVED, IdVerification::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $term = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $search) . '%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }

        $limit = min((int) $request->query('limit', 25), 100);
        return IdVerificationResource::collection($query->paginate($limit));
    }

    public function show(IdVerification $verification): JsonResponse
    {
        $verification->load(['user', 'reviewer']);
        return response()->json(['data' => new IdVerificationResource($verification)]);
    }

    public function download(IdVerification $verification): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($verification->file_path), 404);
        return Storage::disk('local')->response(
            $verification->file_path,
            basename($verification->file_path),
            ['Content-Type' => $verification->mime_type],
        );
    }

    public function approve(IdVerification $verification, Request $request): JsonResponse
    {
        abort_if($verification->status !== IdVerification::STATUS_PENDING, 422, 'Verification is not pending.');

        $validated = $request->validate([
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $verification->update([
            'status' => IdVerification::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'expires_at' => $validated['expires_at'] ?? now()->addYears(2)->toDateString(),
        ]);

        User::where('id', $verification->user_id)->update([
            'verification_status' => User::VERIFY_VERIFIED,
            'verified_at' => now(),
        ]);

        return response()->json(['data' => new IdVerificationResource($verification->fresh(['user', 'reviewer']))]);
    }

    public function reject(IdVerification $verification, Request $request): JsonResponse
    {
        abort_if($verification->status !== IdVerification::STATUS_PENDING, 422, 'Verification is not pending.');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $verification->update([
            'status' => IdVerification::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        User::where('id', $verification->user_id)->update([
            'verification_status' => User::VERIFY_REJECTED,
        ]);

        return response()->json(['data' => new IdVerificationResource($verification->fresh(['user', 'reviewer']))]);
    }
}
