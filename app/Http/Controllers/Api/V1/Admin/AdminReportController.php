<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function revenue(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()));
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();
        $groupBy = $request->query('groupBy', 'day');
        $format = match ($groupBy) {
            'week' => '%Y-%W',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $rows = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PREP, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED, Order::STATUS_REFUNDED])
            ->selectRaw("strftime('$format', created_at) as bucket, SUM(total_pence) as revenue_pence, COUNT(*) as count")
            ->groupBy('bucket')->orderBy('bucket')->get();

        return response()->json(['data' => $rows]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()));
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();

        $rows = OrderItem::whereHas('order', fn ($q) => $q->whereBetween('created_at', [$from, $to])
                ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_FAILED]))
            ->selectRaw('product_id, SUM(quantity) as units, SUM(line_total_pence) as revenue_pence')
            ->groupBy('product_id')->orderByDesc('revenue_pence')->limit(20)->get();

        return response()->json(['data' => $rows]);
    }

    public function exportOrders(Request $request)
    {
        $filename = 'orders-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['reference', 'created_at', 'status', 'customer_email', 'total_pence']);
            Order::with('customer')->chunkById(500, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->reference,
                        $order->created_at->toIso8601String(),
                        $order->status,
                        $order->customer->email,
                        $order->total_pence,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
