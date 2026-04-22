<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? 'Order update' }}</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:#f6f6f6; margin:0; padding:24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="padding:24px; border-bottom:1px solid #eee;">
                <strong style="font-size:20px;">{{ config('app.name') }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:24px; font-size:15px; color:#222; line-height:1.5;">
                {!! $slot !!}
            </td>
        </tr>
        <tr>
            <td style="padding:16px 24px; font-size:12px; color:#888; border-top:1px solid #eee;">
                Order reference: <strong>{{ $order->reference }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
