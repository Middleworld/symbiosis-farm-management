<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Successful - Middle World Farms</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        :root {
            --mwf-green: #2d5016;
            --mwf-light-green: #5a7c3e;
        }

        body {
            background: linear-gradient(135deg, var(--mwf-green) 0%, var(--mwf-light-green) 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-card {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }

        h1 {
            color: var(--mwf-green);
            margin-bottom: 1rem;
        }

        .btn-dashboard {
            background: var(--mwf-green);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-dashboard:hover {
            background: var(--mwf-light-green);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .info-text {
            color: #6c757d;
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        .auto-redirect {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">✅</div>
        <h1>Login Successful!</h1>
        <p class="lead">Welcome back, {{ $user->name }}</p>
        <p class="info-text">
            You can now access the admin dashboard with FarmOS and FieldKit embedded via iframes.
        </p>
        
        <a href="/dashboard" class="btn-dashboard">
            Go to Dashboard →
        </a>

        <div class="auto-redirect">
            <small>Redirecting automatically in <span id="countdown">3</span> seconds...</small>
        </div>
    </div>

    <script>
        let seconds = 3;
        const countdownEl = document.getElementById('countdown');
        
        const interval = setInterval(() => {
            seconds--;
            if (countdownEl) {
                countdownEl.textContent = seconds;
            }
            
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '/dashboard';
            }
        }, 1000);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>