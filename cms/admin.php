<?php
/**
 * CMS Admin Panel - EXAKT som Next.js-versionen
 * Dashboard med CTA-knappar
 */

require_once __DIR__ . '/../config.example.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/validation.php';

// Hantera logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: /cms/admin.php');
    exit;
}

// Hantera login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!csrf_verify()) {
        $error = 'CSRF-validering misslyckades';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            login($username);
            header('Location: /cms/admin-dashboard.php');
            exit;
        } else {
            $error = 'Felaktigt användarnamn eller lösenord';
        }
    }
                </div>
            </div>
            
            <div class="card" style="margin-top: 2rem;">
                <h3 class="card__title">Funktioner</h3>
                <ul style="list-style: disc; padding-left: 1.5rem;">
                    <li>Inline-redigering av text (klicka på text för att redigera)</li>
                    <li>Bilduppladdning (klicka på bilder för att ändra)</li>
                    <li>Automatisk sparning</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary) 100%);
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            margin: 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: white;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: rgba(255, 255, 255, 0.9);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: var(--font-medium);
        }
        .error {
            background-color: var(--color-error);
            color: white;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>CMS Admin Login</p>
            </div>
            
            <div class="card">
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="username">Användarnamn</label>
                        <input type="text" id="username" name="username" class="input" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Lösenord</label>
                        <input type="password" id="password" name="password" class="input" required>
                    </div>
                    
                    <button type="submit" class="button button--primary" style="width: 100%;">
                        Logga in
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
