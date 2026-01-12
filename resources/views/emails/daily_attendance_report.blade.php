<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background-color: #f4f4f4;
            padding: 0;
            line-height: 1.6;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 0;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 22px;
            margin-bottom: 10px;
            font-weight: 600;
            line-height: 1.3;
        }
        .header .date-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            margin-top: 8px;
        }
        .header .alert-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .content {
            padding: 20px 15px;
        }
        .greeting {
            font-size: 16px;
            color: #333333;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        h3 {
            margin: 18px 0 12px 0;
            color: #333333;
            font-size: 16px;
            font-weight: 600;
        }
        
        /* Table Wrapper - Remove horizontal scroll */
        .table-wrapper {
            margin: 15px 0;
            overflow: visible;
        }
        
        /* Attendance Table - Mobile First */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            font-size: 13px;
        }
        .attendance-table thead {
            background-color: #667eea;
            color: #ffffff;
        }
        .attendance-table th,
        .attendance-table td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .attendance-table th {
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }
        .attendance-table td {
            word-wrap: break-word;
            font-size: 13px;
        }
        .attendance-table td strong {
            display: block;
            font-size: 14px;
            margin-bottom: 2px;
            color: #262626;
        }
        .attendance-table td small {
            display: block;
            font-size: 11px;
            color: #8c8c8c;
        }
        
        /* Column widths for mobile */
        .attendance-table th:nth-child(1),
        .attendance-table td:nth-child(1) {
            width: 12%;
        }
        .attendance-table th:nth-child(2),
        .attendance-table td:nth-child(2) {
            width: 40%;
        }
        .attendance-table th:nth-child(3),
        .attendance-table td:nth-child(3) {
            width: 24%;
        }
        .attendance-table th:nth-child(4),
        .attendance-table td:nth-child(4) {
            width: 24%;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px 15px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            font-size: 12px;
            color: #666666;
            margin: 5px 0;
            line-height: 1.5;
        }
        .copyright {
            font-size: 11px;
            color: #999999;
            margin-top: 10px;
        }
        
        /* Mobile optimization */
        @media only screen and (max-width: 480px) {
            .header {
                padding: 25px 15px;
            }
            .header h1 {
                font-size: 20px;
            }
            .header .alert-icon {
                font-size: 36px;
            }
            .header .date-badge {
                font-size: 12px;
                padding: 5px 12px;
            }
            .content {
                padding: 15px 10px;
            }
            .greeting {
                font-size: 15px;
            }
            h3 {
                font-size: 15px;
                margin: 15px 0 10px 0;
            }
            .attendance-table {
                font-size: 12px;
            }
            .attendance-table th,
            .attendance-table td {
                padding: 8px 5px;
            }
            .attendance-table th {
                font-size: 10px;
            }
            .attendance-table td strong {
                font-size: 13px;
            }
            .attendance-table td small {
                font-size: 10px;
            }
            .footer {
                padding: 15px 10px;
            }
            .footer p {
                font-size: 11px;
            }
        }
        
        /* Extra small devices */
        @media only screen and (max-width: 360px) {
            .header h1 {
                font-size: 18px;
            }
            .header .alert-icon {
                font-size: 32px;
            }
            .attendance-table {
                font-size: 11px;
            }
            .attendance-table th,
            .attendance-table td {
                padding: 6px 4px;
            }
            .attendance-table th {
                font-size: 9px;
            }
            .attendance-table td strong {
                font-size: 12px;
            }
            .attendance-table td small {
                font-size: 9px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="alert-icon">📋</div>
            <h1>Daily Attendance Report</h1>
            <div class="date-badge">
                {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
            </div>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $admin->name ?? 'Admin' }},
            </div>

            <h3>Detailed Attendance:</h3>
            <div class="table-wrapper">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Labour Name</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $count = 1;
                        @endphp
                        @forelse($attendance as $record)
                            <tr>
                                <td>{{ $count }}</td>
                                <td>
                                    <strong>{{ $record->labour->name ?? 'N/A' }}</strong>
                                    @if($record->labour->employee_id ?? null)
                                        <small>{{ $record->labour->employee_id }}</small>
                                    @endif
                                </td>
                                <td>{{ $record->sign_in ? \Carbon\Carbon::parse($record->sign_in)->format('h:i A') : '-' }}</td>
                                <td>{{ $record->sign_out ? \Carbon\Carbon::parse($record->sign_out)->format('h:i A') : '-' }}</td>
                            </tr>
                            @php
                            $count++;
                            @endphp
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: #8c8c8c;">
                                    No attendance records found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div class="footer">
            <p>This is an automated system report. Please do not reply to this email.</p>
            <p>For any queries, please contact the HR department.</p>
            <div class="copyright">
                &copy; {{ date('Y') }} Attendance Management System. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>