<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            line-height: 1.6;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .header p {
            font-size: 14px;
            opacity: 0.95;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message {
            font-size: 15px;
            color: #555555;
            margin-bottom: 25px;
            line-height: 1.8;
        }
        .quotation-details {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .quotation-details h3 {
            font-size: 16px;
            color: #333333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #666666;
            font-size: 14px;
        }
        .detail-value {
            color: #333333;
            font-size: 14px;
        }
        .cta-button {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button a {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .cta-button a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            font-size: 14px;
            color: #856404;
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
            font-size: 18px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 10px;
        }
        .company-details {
            font-size: 13px;
            color: #666666;
            line-height: 1.8;
        }
        .company-details p {
            margin: 5px 0;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }
        .copyright {
            font-size: 12px;
            color: #999999;
            margin-top: 15px;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 25px 20px;
            }
            .header {
                padding: 30px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            .cta-button a {
                padding: 12px 30px;
                font-size: 14px;
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
            <h1>New Quotation</h1>
            <p>Your quotation is ready for review</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">
                Hello {{ $customer->name }},
            </div>

            <div class="message">
                Thank you for your interest in our products/services. We are pleased to provide you with the following quotation as per your requirements.
            </div>

            <!-- Quotation Details -->
            <div class="quotation-details">
                <h3>Quotation Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Quotation Number:</span>
                    <span class="detail-value">#{{ $quotation->batch_no }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($quotation->created_at)->format('d M, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Valid Until:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($publicLink->expiry_time)->format('d M, Y') }}</span>
                </div>
                @if($quotation->total_amount)
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">₹{{ number_format($quotation->total_amount, 2) }}</span>
                </div>
                @endif
            </div>

            <!-- Call to Action -->
            <div class="cta-button">
                <a href="{{ ENV('FRONTEND_APP_URL') }}/quotation/{{ $publicLink->link }}" target="_blank">View Quotation</a>
            </div>

            <!-- Note Section -->
            <div class="note">
                <strong>Note:</strong> This quotation is valid until {{ \Carbon\Carbon::parse($publicLink->expiry_time)->format('d M, Y') }}. Please review and let us know if you have any questions.
            </div>

            <div class="message">
                If you have any questions or need clarification, please don't hesitate to contact us. We look forward to working with you.
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
                    @if($company->gst_no )
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