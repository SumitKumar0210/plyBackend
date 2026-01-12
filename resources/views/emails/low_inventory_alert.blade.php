<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Inventory Alert</title>
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
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: #ffffff;
            padding: 35px 25px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .header .alert-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.95;
        }
        .content {
            padding: 35px 25px;
        }
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 18px;
            font-weight: 500;
        }
        .message {
            font-size: 15px;
            color: #555555;
            margin-bottom: 22px;
            line-height: 1.7;
        }
        .alert-summary {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 18px;
            margin: 22px 0;
            border-radius: 4px;
        }
        .alert-summary h3 {
            font-size: 16px;
            color: #856404;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .alert-summary p {
            font-size: 14px;
            color: #856404;
            margin: 6px 0;
        }
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 22px 0;
        }
        .materials-table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .materials-table thead {
            background-color: #f44336;
            color: #ffffff;
        }
        .materials-table th,
        .materials-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
        }
        .materials-table th {
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .materials-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .materials-table tbody tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-critical {
            background-color: #ffebee;
            color: #c62828;
        }
        .status-low {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            font-size: 13px;
            color: #666666;
            margin: 6px 0;
        }
        .copyright {
            font-size: 12px;
            color: #999999;
            margin-top: 12px;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 25px 15px;
            }
            .header {
                padding: 30px 15px;
            }
            .header h1 {
                font-size: 24px;
            }
            .table-wrapper {
                margin: 20px -15px;
                padding: 0 15px;
            }
            .materials-table {
                font-size: 12px;
            }
            .materials-table th,
            .materials-table td {
                padding: 10px 8px;
            }
            .status-badge {
                font-size: 10px;
                padding: 3px 8px;
            }
            .alert-summary {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="alert-icon">⚠️</div>
            <h1>Low Inventory Alert</h1>
            <p>Urgent attention required for inventory replenishment</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">
                Hello {{ $admin->name ?? 'Admin' }},
            </div>

            <div class="message">
                This is an automated alert to notify you that several materials in your inventory have reached or fallen below their minimum stock levels and require urgent replenishment.
            </div>

            <!-- Alert Summary -->
            <div class="alert-summary">
                <h3>📊 Alert Summary</h3>
                <p><strong>Total Items:</strong> {{ count($materials) }} material(s) require immediate attention</p>
                <p><strong>Alert Date:</strong> {{ \Carbon\Carbon::now()->format('d M, Y h:i A') }}</p>
                <p><strong>Priority:</strong> HIGH - Urgent Requirement Flagged</p>
            </div>

            <!-- Materials Table -->
            <h3 style="margin: 22px 0 12px 0; color: #333333;">Materials Requiring Attention:</h3>
            <div class="table-wrapper">
                <table class="materials-table">
                    <thead>
                        <tr>
                            <th>Material Name</th>
                            <th>Min. Qty</th>
                            <th>Ava. Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materials as $material)
                        @php
                            $availableQty = $material->available_qty ?? 0;
                            $minimumQty = $material->minimum_qty ?? 0;
                            $isOutOfStock = $availableQty == 0;
                            $isCritical = !$isOutOfStock && $availableQty < ($minimumQty * 0.5);
                        @endphp
                        <tr>
                            <td><strong>{{ $material->name ?? 'N/A' }}</strong></td>
                            <td>{{ $minimumQty }}</td>
                            <td>{{ $availableQty }}</td>
                            <td>
                                <span class="status-badge {{ $isOutOfStock ? 'status-critical' : ($isCritical ? 'status-critical' : 'status-low') }}">
                                    {{ $isOutOfStock ? 'OUT OF STOCK' : ($isCritical ? 'CRITICAL' : 'LOW') }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="message">
                <strong>Recommended Action:</strong> Please review the inventory levels and initiate purchase orders for the affected materials to prevent production delays or service disruptions.
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>This is an automated system alert. Please do not reply to this email.</p>
            <p>If you have any questions, please contact your inventory management team.</p>
            <div class="copyright">
                &copy; {{ date('Y') }} Inventory Management System. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>