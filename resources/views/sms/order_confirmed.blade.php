{{ config('app.name') }}: Order {{ $order->reference }} confirmed. Total £{{ number_format($order->total_pence / 100, 2) }}. We'll text you when it's out for delivery.
