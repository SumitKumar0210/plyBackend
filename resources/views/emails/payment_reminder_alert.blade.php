<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminder Alert</title>
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
            max-width: 900px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
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
            border-left: 4px solid #ff9800;
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
        .payments-table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .payments-table thead {
            background-color: #ff9800;
            color: #ffffff;
        }
        .payments-table th,
        .payments-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
            white-space: nowrap;
        }
        .payments-table th {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .payments-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .payments-table tbody tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        .status-overdue {
            background-color: #ffebee;
            color: #c62828;
        }
        .status-due-soon {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        .status-due-today {
            background-color: #ffe0b2;
            color: #e65100;
        }
        .overdue-row {
            background-color: #ffebee !important;
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
            .payments-table {
                font-size: 11px;
            }
            .payments-table th,
            .payments-table td {
                padding: 10px 6px;
            }
            .status-badge {
                font-size: 9px;
                padding: 3px 6px;
            }
            .alert-summary {
                padding: 15px;
            }
            .message ul {
                margin: 10px 0 0 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="alert-icon">💰</div>
            <h1>Payment Reminder Alert</h1>
            <p>Upcoming and overdue payment obligations require your attention</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">
                Hello {{ $admin->name ?? 'Admin' }},
            </div>

            <div class="message">
                This is an automated reminder regarding purchase orders with upcoming payment deadlines or overdue payments. Please review the following orders and take necessary action to ensure timely payment processing.
            </div>

            <!-- Alert Summary -->
            <div class="alert-summary">
                <h3>📊 Payment Summary</h3>
                <p><strong>Total Orders:</strong> {{ count($payments) }} purchase order(s) require attention</p>
                <p><strong>Alert Date:</strong> {{ $today }}</p>
                <p><strong>Priority:</strong> Payment due within 5 days or overdue</p>
            </div>

            <!-- Payments Table -->
            <h3 style="margin: 22px 0 12px 0; color: #333333;">Purchase Orders Requiring Payment:</h3>
            <div class="table-wrapper">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Vendor</th>
                            <th>Receiving Date</th>
                            <th>Credit Days</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Days Left</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            @php
                                $order = $payment['order'] ?? null;
                                $vendor = $order->vendor ?? null;
                                $creditDays = $order->credit_days ?? 0;
                                $dueAmount = ($order->due_amount ?? 0) > 0 ? $order->due_amount : ($order->grand_total ?? 0);
                                $isOverdue = $payment['is_overdue'] ?? false;
                                $daysRemaining = $payment['days_remaining'] ?? 0;
                                $overdueDays = $payment['overdue_days'] ?? 0;
                            @endphp
                            <tr class="{{ $isOverdue ? 'overdue-row' : '' }}">
                                <td><strong>#{{ $order->id ?? 'N/A' }}</strong></td>
                                <td>{{ $vendor->name ?? 'N/A' }}</td>
                                <td>{{ $payment['order_date'] ?? 'N/A' }}</td>
                                <td>{{ $creditDays }} days</td>
                                <td><strong>{{ $payment['payment_due_date'] ?? 'N/A' }}</strong></td>
                                <td>{{ $dueAmount > 0 ? '₹' . number_format($dueAmount, 2) : 'N/A' }}</td>
                                <td>
                                    @if($isOverdue)
                                        <strong style="color: #c62828;">-{{ $overdueDays }} days</strong>
                                    @else
                                        {{ $daysRemaining }} days
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge {{ $isOverdue ? 'status-overdue' : ($daysRemaining == 0 ? 'status-due-today' : 'status-due-soon') }}">
                                        {{ $isOverdue ? 'OVERDUE' : ($daysRemaining == 0 ? 'DUE TODAY' : 'DUE SOON') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="message">
                <strong>Recommended Action:</strong> 
                <ul style="margin: 10px 0 0 20px; line-height: 2;">
                    <li>Review overdue payments immediately and process them as priority</li>
                    <li>Schedule payments for orders due within the next 5 days</li>
                    <li>Contact vendors if you need to negotiate payment terms</li>
                    <li>Update payment status in the system once processed</li>
                </ul>
            </div>

            @php
                $overdueCount = collect($payments)->where('is_overdue', true)->count();
            @endphp

            @if($overdueCount > 0)
            <div style="background-color: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 18px 0; border-radius: 4px;">
                <strong style="color: #c62828;">⚠️ Critical Alert:</strong>
                <span style="color: #c62828;">You have {{ $overdueCount }} overdue payment(s). Immediate action required to maintain vendor relationships.</span>
            </div>
            @endif
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>This is an automated system alert. Please do not reply to this email.</p>
            <p>If you have any questions, please contact your accounts payable team.</p>
            <div class="copyright">
                &copy; {{ date('Y') }} Purchase Order Management System. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>