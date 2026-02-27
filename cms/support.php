<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $error = 'Ämne och meddelande krävs.';
    } else {
        try {
            require_once __DIR__ . '/tickets-db.php';
            $admin_user = defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'admin';
            ticket_create([
                'source' => 'admin',
                'name' => $admin_user,
                'subject' => $subject,
                'message' => $message,
            ]);
            $sent = true;
        } catch (\Throwable $e) {
            $error = 'Ärendet kunde inte skapas. Försök igen.';
            error_log('Ticket creation failed: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #033234;
            min-height: 100vh;
            color: rgba(255,255,255,1.0);
        }
        .page-content {
            padding: 3rem 1.5rem;
        }
        .container {
            max-width: 48rem;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: rgba(255,255,255,0.50);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: rgba(255,255,255,1.0);
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: rgba(255,255,255,0.50);
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
            color: rgba(255,255,255,1.0);
            margin-bottom: 1rem;
        }
        .success-text {
            color: rgba(255,255,255,0.50);
            margin-bottom: 2rem;
        }
        .form-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.5rem;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
            background-color: rgba(255,255,255,0.05);
            color: white;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: #379b83;
            box-shadow: 0 0 0 3px rgba(55, 155, 131, 0.1);
        }
        .form-textarea {
            resize: none;
            min-height: 10rem;
        }
        .button-primary {
            width: 100%;
            background: #379b83;
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
                <div class="success-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <h1 class="success-title">Tack!</h1>
                <p class="success-text">
                    Ditt ärende har registrerats. Vi återkommer så snart som möjligt.
                </p>
                <a href="/dashboard" class="button-primary">
                    Tillbaka till dashboard
                </a>
            </div>
        <?php else: ?>
            <a href="/dashboard" class="back-link">← Tillbaka</a>

            <?php if (!empty($error)): ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0;"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <h1 class="title">Support</h1>
            <p class="subtitle">
                Skapa ett supportärende så återkommer vi.
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
                        Skicka ärende
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
