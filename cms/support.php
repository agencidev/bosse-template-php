<?php
require_once __DIR__ . '/../config.example.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $sent = true;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
            min-height: 100vh;
        }
        .page-content {
            padding: 3rem 1.5rem;
        }
        .container {
            max-width: 36rem;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: #737373;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #18181b;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: #18181b;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #737373;
            margin-bottom: 2rem;
        }
        .success-card {
            text-align: center;
            padding: 4rem 1.5rem;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        .success-title {
            font-size: 2rem;
            font-weight: bold;
            color: #18181b;
            margin-bottom: 1rem;
        }
        .success-text {
            color: #737373;
            margin-bottom: 2rem;
        }
        .form-card {
            background: white;
            border-radius: 1.5rem;
            border: 1px solid #e5e5e5;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 0.5rem;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid #d4d4d4;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: #fe4f2a;
            box-shadow: 0 0 0 3px rgba(254, 79, 42, 0.1);
        }
        .form-textarea {
            resize: none;
            min-height: 10rem;
        }
        .button-primary {
            width: 100%;
            background: #fe4f2a;
            color: white;
            padding: 0.875rem 1.5rem;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <?php if ($sent): ?>
            <div class="success-card">
                <div class="success-icon">✅</div>
                <h1 class="success-title">Tack!</h1>
                <p class="success-text">
                    Ditt meddelande har skickats. Vi återkommer så snart som möjligt.
                </p>
                <a href="/dashboard" class="button-primary">
                    Tillbaka till dashboard
                </a>
            </div>
        <?php else: ?>
            <a href="/dashboard" class="back-link">← Tillbaka</a>
            
            <h1 class="title">Support</h1>
            <p class="subtitle">
                Har du frågor eller behöver hjälp? Skicka ett meddelande så återkommer vi.
            </p>

            <div class="form-card">
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="subject">Ämne *</label>
                        <input 
                            type="text" 
                            id="subject" 
                            name="subject" 
                            class="form-input" 
                            placeholder="Vad gäller det?"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="message">Meddelande *</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-textarea"
                            placeholder="Beskriv ditt ärende..."
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="button-primary">
                        Skicka meddelande
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
