<?php
/**
 * CMS Login - EXAKT som LoginForm.jsx i Next.js
 */

require_once __DIR__ . '/../config.example.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/session.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /cms/admin-dashboard.php');
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: /cms/login.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
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
            ring: 2px solid #ff5722;
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
            background-color: #ff5722;
            color: white;
            font-weight: 600;
            padding: 0.75rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .button:hover {
            background-color: #e64a19;
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
            <svg class="logo" viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg">
                <text x="10" y="40" font-family="Arial, sans-serif" font-size="32" font-weight="bold" fill="#18181b">agenci</text>
            </svg>
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
