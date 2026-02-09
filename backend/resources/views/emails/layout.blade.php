<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Omersia' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333333;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background-color: #000000;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-footer {
            background-color: #f9f9f9;
            padding: 30px;
            text-align: center;
            font-size: 12px;
            color: #666666;
            border-top: 1px solid #e5e5e5;
        }
        .greeting {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
            color: #000000;
        }
        .content {
            font-size: 14px;
            line-height: 1.6;
            color: #333333;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background-color: #000000;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #333333;
        }
        .info-box {
            background-color: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-box-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
            color: #000000;
        }
        .info-box-content {
            font-size: 13px;
            line-height: 1.6;
            color: #666666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 2px solid #e5e5e5;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e5e5;
            font-size: 13px;
        }
        .total-row {
            font-weight: 600;
            font-size: 14px;
        }
        .total-row td {
            border-top: 2px solid #000000;
            border-bottom: none;
            padding-top: 15px;
        }
        .divider {
            height: 1px;
            background-color: #e5e5e5;
            margin: 30px 0;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .alert-error {
            background-color: #f8d7da;
            border-color: #dc3545;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #28a745;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>{{ config('app.name', 'Omersia') }}</h1>
        </div>

        <div class="email-body">
            @yield('content')
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Omersia') }}. Tous droits réservés.</p>
            @if(isset($shopInfo))
                <p style="margin-top: 10px;">
                    {{ $shopInfo['name'] ?? '' }}<br>
                    {{ $shopInfo['email'] ?? '' }}<br>
                    {{ $shopInfo['phone'] ?? '' }}
                </p>
            @endif
        </div>
    </div>
</body>
</html>
