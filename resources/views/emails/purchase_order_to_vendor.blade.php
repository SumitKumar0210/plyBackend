<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Purchase Order</title>

    @php
        $poNumber = $purchaseOrder->purchase_no
            ?? $purchaseOrder->po_no
            ?? $purchaseOrder->batch_no
            ?? 'N/A';

        $orderDate = $purchaseOrder->order_date
            ?? $purchaseOrder->created_at;
    @endphp

    <style>
        /* ---- YOUR ORIGINAL STYLES (UNCHANGED) ---- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            line-height: 1.6;
        }
        .email-container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .header {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            padding: 40px 30px;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .content { padding: 40px 30px; }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #fef3c7;
        }
        .detail-label { font-weight: 600; color: #666; }
        .detail-value { color: #333; text-align: right; }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
        }
    </style>
</head>

<body>
<div class="email-container">

    {{-- HEADER --}}
    <div class="header">
        @if(!empty($company->logo))
            <img src="{{ env('APP_URL') }}/{{ $company->logo }}" class="logo" alt="{{ $company->app_name }}">
        @endif
        <h1>Purchase Order</h1>
        <p>New purchase order from {{ $company->app_name }}</p>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        <p>Hello {{ $vendor->name ?? 'Valued Vendor' }},</p>

        <p>
            We are pleased to issue you a purchase order. Please review the details below
            and confirm your acceptance.
        </p>

        {{-- PO DETAILS --}}
        <div style="background:#fffbeb;padding:20px;border-left:4px solid #f59e0b;margin:25px 0;">
            <h3>📋 Purchase Order Details</h3>

            <div class="detail-row">
                <span class="detail-label">PO Number:</span>
                <span class="detail-value">#{{ $poNumber }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value">
                    {{ \Carbon\Carbon::parse($orderDate)->format('d M, Y') }}
                </span>
            </div>

            @if(!empty($purchaseOrder->expected_delivery_date))
                <div class="detail-row">
                    <span class="detail-label">Expected Delivery:</span>
                    <span class="detail-value">
                        {{ \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date)->format('d M, Y') }}
                    </span>
                </div>
            @endif

            @if(!empty($purchaseOrder->credit_days))
                <div class="detail-row">
                    <span class="detail-label">Credit Days:</span>
                    <span class="detail-value">{{ $purchaseOrder->credit_days }} days</span>
                </div>
            @endif

            @if(!empty($purchaseOrder->grand_total))
                <div class="detail-row">
                    <span class="detail-label">Grand Total:</span>
                    <span class="detail-value">
                        <strong>₹{{ number_format($purchaseOrder->grand_total, 2) }}</strong>
                    </span>
                </div>
            @endif

            @if(isset($purchaseOrder->status))
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        {{ $purchaseOrder->status == 1 ? 'Active' : 'Pending' }}
                    </span>
                </div>
            @endif
        </div>

        {{-- VENDOR --}}
        <div style="background:#f8f9fa;padding:20px;">
            <h3>🏭 Vendor Information</h3>
            <p><strong>{{ $vendor->name ?? 'N/A' }}</strong></p>
            @if(!empty($vendor->email)) <p>✉️ {{ $vendor->email }}</p> @endif
            @if(!empty($vendor->mobile)) <p>📞 {{ $vendor->mobile }}</p> @endif
        </div>

        {{-- CTA --}}
        @if(!empty($publicLink->link))
            <p style="text-align:center;margin:30px 0;">
                <a href="{{ env('FRONTEND_APP_URL') }}/purchase-order/{{ $publicLink->link }}"
                   style="background:#f59e0b;color:#fff;padding:14px 35px;border-radius:30px;text-decoration:none;">
                    View Complete Purchase Order
                </a>
            </p>
        @endif

        <p><strong>⚠️ Important:</strong> Please acknowledge this purchase order within 24 hours.</p>

    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <p><strong>{{ $company->app_name }}</strong></p>
        <p>&copy; {{ date('Y') }} {{ $company->app_name }}. All rights reserved.</p>
    </div>

</div>
</body>
</html>
