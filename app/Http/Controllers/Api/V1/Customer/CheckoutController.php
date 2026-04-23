<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\CreateOrderFromCart;
use App\Domain\Payments\Services\StripeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutSessionRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class CheckoutController extends Controller
{
    public function session(CheckoutSessionRequest $request, CreateOrderFromCart $createOrder, StripeService $stripe): JsonResponse
    {
        $user = $request->user();
        $address = Address::where('user_id', $user->id)->findOrFail($request->input('address_id'));

        $order = $createOrder->execute(
            customer: $user,
            items: $request->input('items'),
            address: $address,
            scheduledFor: $request->filled('scheduled_for') ? Carbon::parse($request->input('scheduled_for')) : null,
            customerNotes: $request->input('customer_notes'),
            deliveryTier: (string) $request->input('delivery_tier', 'standard'),
            statementOfUseAccepted: (bool) $request->boolean('statement_of_use_accepted'),
            n2oAgreementAccepted: (bool) $request->boolean('n2o_agreement_accepted'),
        );

        $session = $stripe->createCheckoutSession($order);

        return response()->json([
            'data' => [
                'order' => new OrderResource($order),
                'checkout_url' => $session->url,
                'stripe_session_id' => $session->id,
            ],
        ], 201);
    }

    public function confirm(string $sessionId): JsonResponse
    {
        $order = Order::where('stripe_session_id', $sessionId)->with(['items', 'address'])->first();

        if (! $order) {
            return response()->json(['data' => ['status' => 'pending']], 202);
        }

        return response()->json(['data' => new OrderResource($order)]);
    }
}
