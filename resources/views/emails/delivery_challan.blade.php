<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Delivery Challan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
            background-color: #ffffff;
            padding: 10px;
            border-radius: 8px;
        }
        .header h1 {
            font-size: clamp(24px, 5vw, 28px);
            margin-bottom: 8px;
            font-weight: 600;
        }
        .header p {
            font-size: clamp(13px, 3vw, 14px);
            opacity: 0.95;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: clamp(16px, 4vw, 18px);
            color: #333333;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message {
            font-size: clamp(14px, 3.5vw, 15px);
            color: #555555;
            margin-bottom: 25px;
            line-height: 1.8;
        }
        .challan-details {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .challan-details h3 {
            font-size: clamp(15px, 3.5vw, 16px);
            color: #333333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #d1fae5;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #666666;
            font-size: clamp(13px, 3vw, 14px);
        }
        .detail-value {
            color: #333333;
            font-size: clamp(13px, 3vw, 14px);
            text-align: right;
            word-break: break-word;
        }
        .shipping-info {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .shipping-info h3 {
            font-size: clamp(15px, 3.5vw, 16px);
            color: #333333;
            margin-bottom: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .shipping-address {
            font-size: clamp(13px, 3vw, 14px);
            color: #555555;
            line-height: 1.8;
        }
        .cta-button {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button a {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: clamp(14px, 3.5vw, 16px);
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        .cta-button a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }
        .note {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 25px 0;
            font-size: clamp(13px, 3vw, 14px);
            color: #1e40af;
            border-radius: 4px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .company-name {
            font-size: clamp(16px, 4vw, 18px);
            font-weight: 600;
            color: #333333;
            margin-bottom: 10px;
        }
        .company-details {
            font-size: clamp(12px, 3vw, 13px);
            color: #666666;
            line-height: 1.8;
        }
        .company-details p {
            margin: 5px 0;
            word-wrap: break-word;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }
        .copyright {
            font-size: clamp(11px, 2.5vw, 12px);
            color: #999999;
            margin-top: 15px;
        }
        
        /* Tablet devices */
        @media only screen and (max-width: 768px) {
            body {
                padding: 15px;
            }
            .content {
                padding: 30px 20px;
            }
            .header {
                padding: 30px 20px;
            }
        }
        
        /* Mobile devices */
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .email-container {
                border-radius: 4px;
            }
            .content {
                padding: 25px 15px;
            }
            .header {
                padding: 25px 15px;
            }
            .detail-row {
                flex-direction: column;
                gap: 5px;
                padding: 10px 0;
            }
            .detail-value {
                text-align: left;
            }
            .cta-button a {
                padding: 12px 30px;
                display: block;
                width: 100%;
            }
            .challan-details,
            .shipping-info,
            .note {
                padding: 15px;
                margin: 20px 0;
            }
            .footer {
                padding: 25px 15px;
            }
        }
        
        /* Small mobile devices */
        @media only screen and (max-width: 400px) {
            body {
                padding: 8px;
            }
            .content {
                padding: 20px 12px;
            }
            .header {
                padding: 20px 12px;
            }
            .challan-details,
            .shipping-info,
            .note {
                padding: 12px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a;
            }
            .email-container {
                background-color: #2d2d2d;
            }
            .greeting {
                color: #e0e0e0;
            }
            .message {
                color: #b0b0b0;
            }
            .detail-label {
                color: #a0a0a0;
            }
            .detail-value {
                color: #e0e0e0;
            }
            .shipping-info {
                background-color: #383838;
            }
            .shipping-info h3 {
                color: #e0e0e0;
            }
            .shipping-address {
                color: #b0b0b0;
            }
            .footer {
                background-color: #242424;
            }
            .company-name {
                color: #e0e0e0;
            }
            .company-details {
                color: #a0a0a0;
            }
        }
        
        /* Print styles */
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .email-container {
                box-shadow: none;
                max-width: 100%;
            }
            .cta-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            @if($company->logo)
                <img src="{{ENV('APP_URL')}}/{{$company->logo }}" alt="{{ $company->company_name }}" class="logo">
            @endif
            <h1>Delivery Challan</h1>
            <p>Your delivery challan is ready</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">
                Hello {{ $customer->name }},
            </div>

            <div class="message">
                Thank you for your business. Please find the delivery challan details for your recent order. This challan accompanies the goods being delivered to you.
            </div>

            <!-- Challan Details -->
            <div class="challan-details">
                <h3>📋 Challan Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Challan Number:</span>
                    <span class="detail-value">#{{ $challan->invoice_no }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($challan->created_at)->format('d M, Y') }}</span>
                </div>
                @if($challan->order_no)
                <div class="detail-row">
                    <span class="detail-label">Order Number:</span>
                    <span class="detail-value">{{ $challan->order_no }}</span>
                </div>
                @endif
                @if($challan->total_amount)
                <div class="detail-row">
                    <span class="detail-label">Total Value:</span>
                    <span class="detail-value">₹{{ number_format($challan->total_amount, 2) }}</span>
                </div>
                @endif
            </div>

            <!-- Shipping Information -->
            @if($challan->shippingAddress)
            <div class="shipping-info">
                <h3>📍 Shipping Address</h3>
                <div class="shipping-address">
                    <strong>{{ $challan->shippingAddress->name ?? $customer->name }}</strong><br>
                    @if($challan->shippingAddress->address)
                        {{ $challan->shippingAddress->address }}<br>
                    @endif
                    @if($challan->shippingAddress->city)
                        {{ $challan->shippingAddress->city }}
                    @endif
                    @if($challan->shippingAddress->pincode)
                        - {{ $challan->shippingAddress->pincode }}<br>
                    @endif
                    @if($challan->shippingAddress->state)
                        {{ $challan->shippingAddress->state->name ?? $challan->shippingAddress->state }}<br>
                    @endif
                    @if($challan->shippingAddress->contact)
                        📞 {{ $challan->shippingAddress->contact }}
                    @endif
                </div>
            </div>
            @endif

            <!-- Call to Action -->
            <div class="cta-button">
                <a href="{{ ENV('FRONTEND_APP_URL') }}/challan/{{ $publicLink->link }}" target="_blank">View Challan Details</a>
            </div>

            <!-- Note Section -->
            <div class="note">
                <strong>📌 Important Note:</strong> Please verify the goods received against this challan and report any discrepancies immediately. Keep this challan for your records.
            </div>

            <div class="message">
                If you have any questions regarding this delivery or notice any issues with the shipment, please contact us immediately. We appreciate your business!
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <div class="company-info">
                <div class="company-name">{{ $company->company_name }}</div>
                <div class="company-details">
                    @if($company->address)
                        <p>📍 {{ $company->address }}</p>
                    @endif
                    @if($company->email)
                        <p>✉️ {{ $company->email }}</p>
                    @endif
                    @if($company->contact)
                        <p>📞 {{ $company->contact }}</p>
                    @endif
                    @if($company->gst_no)
                        <p>GST: {{ $company->gst_no }}</p>
                    @endif
                </div>
            </div>

            <div class="divider"></div>

            <div class="copyright">
                &copy; {{ date('Y') }} {{ $company->company_name }}. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>