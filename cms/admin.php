<?php
/**
 * CMS Admin Login - EXAKT som LoginForm.jsx i Next.js
 * Routing: /cms/admin.php = login-sida
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/session.php';

// Handle logout FIRST before any other checks
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Complete logout
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Start new clean session
    session_start();
    
    // Prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    
    // Redirect to login page
    header('Location: /admin');
    exit;
}

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /dashboard');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting check
    $rate_limit = check_login_rate_limit();
    if (!$rate_limit['allowed']) {
        $error = 'För många inloggningsförsök. Vänta ' . $rate_limit['wait_seconds'] . ' sekunder.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Använd timing-safe jämförelse för användarnamn och password_verify för lösenord
        if (hash_equals(ADMIN_USERNAME, $username) && password_verify($password, ADMIN_PASSWORD_HASH)) {
            clear_login_attempts();
            login_user($username);
            header('Location: /dashboard');
            exit;
        } else {
            record_login_attempt();
            $error = 'Felaktigt användarnamn eller lösenord';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logga in - CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fafafa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container {
            width: 100%;
            max-width: 28rem;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            padding: 2rem;
        }
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .logo {
            height: 3rem;
            width: auto;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 0.5rem;
        }
        .input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d4d4d4;
            border-radius: 0.5rem;
            font-size: 1rem;
            outline: none;
            transition: all 0.2s;
        }
        .input:focus {
            border-color: transparent;
            box-shadow: 0 0 0 2px #ff5722;
        }
        .error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .button {
            width: 100%;
            background-color: #fe4f2a;
            color: white;
            padding: 0.875rem;
            border: none;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="/assets/images/logo-light.png" alt="<?php echo SITE_NAME; ?>" class="logo">
        </div>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            
            <div class="form-group">
                <label class="label" for="username">Användarnamn</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="input" 
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="label" for="password">Lösenord</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="input" 
                    required
                >
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <button type="submit" class="button">
                Logga in
            </button>
        </form>
    </div>
</body>
</html>
