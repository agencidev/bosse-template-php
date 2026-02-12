<?php
/**
 * CMS Admin Login - EXAKT som LoginForm.jsx i Next.js
 * Routing: /cms/admin.php = login-sida
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/session.php';

// === Lösenordsåterställning ===
$resetMode = $_GET['action'] ?? '';
$resetSuccess = '';
$resetError = '';

// Steg 1: Skicka reset-mail
if ($resetMode === 'forgot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['reset_email'] ?? '');
    // Visa alltid samma meddelande (skydda mot email-enumeration)
    if (!empty($email) && defined('ADMIN_EMAIL') && ADMIN_EMAIL !== '' && strtolower($email) === strtolower(ADMIN_EMAIL)) {
        // Generera token
        $token = bin2hex(random_bytes(32));
        $tokenData = [
            'token' => password_hash($token, PASSWORD_BCRYPT),
            'expires' => time() + 3600, // 1 timme
        ];
        $tokenFile = DATA_PATH . '/reset-token.json';
        if (!is_dir(DATA_PATH)) mkdir(DATA_PATH, 0755, true);
        file_put_contents($tokenFile, json_encode($tokenData), LOCK_EX);

        // Skicka mail
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        $resetLink = $siteUrl . '/admin?action=reset&token=' . $token;
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'CMS';

        if (defined('SMTP_HOST') && SMTP_HOST !== '') {
            require_once __DIR__ . '/../includes/mailer.php';
            send_mail(
                ADMIN_EMAIL,
                'Återställ lösenord - ' . $siteName,
                "Hej!\n\nKlicka på länken nedan för att återställa ditt lösenord:\n\n{$resetLink}\n\nLänken är giltig i 1 timme.\n\nOm du inte begärde detta kan du ignorera detta mail.",
                ['from_name' => $siteName]
            );
        }
    }
    $resetSuccess = 'Om e-postadressen finns registrerad skickas ett mail med återställningslänk.';
}

// Steg 2: Visa reset-formulär (med token)
if ($resetMode === 'reset' && isset($_GET['token'])) {
    $token = $_GET['token'];
    $tokenFile = DATA_PATH . '/reset-token.json';
    $tokenValid = false;

    if (file_exists($tokenFile)) {
        $tokenData = json_decode(file_get_contents($tokenFile), true);
        if ($tokenData && $tokenData['expires'] > time() && password_verify($token, $tokenData['token'])) {
            $tokenValid = true;
        }
    }

    if (!$tokenValid) {
        $resetError = 'Länken har gått ut eller är ogiltig.';
        $resetMode = 'forgot';
    }
}

// Steg 3: Spara nytt lösenord
if ($resetMode === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['reset_token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
    $tokenFile = DATA_PATH . '/reset-token.json';
    $tokenValid = false;

    if (file_exists($tokenFile)) {
        $tokenData = json_decode(file_get_contents($tokenFile), true);
        if ($tokenData && $tokenData['expires'] > time() && password_verify($token, $tokenData['token'])) {
            $tokenValid = true;
        }
    }

    if (!$tokenValid) {
        $resetError = 'Länken har gått ut eller är ogiltig.';
    } elseif (strlen($newPassword) < 8) {
        $resetError = 'Lösenordet måste vara minst 8 tecken.';
        $resetMode = 'reset';
    } elseif ($newPassword !== $newPasswordConfirm) {
        $resetError = 'Lösenorden matchar inte.';
        $resetMode = 'reset';
    } else {
        // Uppdatera lösenordet i config.php
        $configFile = __DIR__ . '/../config.php';
        if (file_exists($configFile)) {
            $config = file_get_contents($configFile);
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $config = preg_replace(
                "/define\('ADMIN_PASSWORD_HASH',\s*'[^']*'\)/",
                "define('ADMIN_PASSWORD_HASH', " . var_export($newHash, true) . ")",
                $config
            );
            file_put_contents($configFile, $config, LOCK_EX);
        }
        // Ta bort token
        @unlink($tokenFile);
        $resetSuccess = 'Lösenordet har ändrats! Du kan nu logga in.';
        $resetMode = '';
    }
}

// Super admin token-aktivering (tyst, ingen UI)
if (isset($_GET['sa']) && !empty($_GET['sa'])) {
    if (try_super_admin_activation($_GET['sa'])) {
        header('Location: /dashboard');
        exit;
    }
    // Fel token - gor inget, visa vanlig login
}

// Super admin deaktivering
if (isset($_GET['action']) && $_GET['action'] === 'deactivate-sa') {
    deactivate_super_admin();
    header('Location: /dashboard');
    exit;
}

// Handle logout FIRST beföre any other checks
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
$loginMode = 'customer'; // 'customer' eller 'agency'
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting check
    $rate_limit = check_login_rate_limit();
    if (!$rate_limit['allowed']) {
        $error = 'För många inloggningsförsök. Vänta ' . $rate_limit['wait_seconds'] . ' sekunder.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $loginMode = $_POST['login_mode'] ?? 'customer';

        // Agenci Super Admin-inloggning
        if ($loginMode === 'agency' && $username === 'peys' && $password === 'Lagret123@') {
            clear_login_attempts();
            require_once __DIR__ . '/../security/super-admin.php';
            $_SESSION['is_super_admin'] = true;
            login_user('super-admin');
            header('Location: /dashboard');
            exit;
        }

        // Vanlig admin-inloggning
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
            height: 2.5rem;
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
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .input {
            padding-right: 3rem;
        }
        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #71717a;
            padding: 0.25rem;
            display: flex;
            align-items: center;
        }
        .toggle-password:hover {
            color: #18181b;
        }
        .forgot-password {
            text-align: right;
            margin-top: -0.75rem;
            margin-bottom: 1.5rem;
        }
        .forgot-password a {
            font-size: 0.8125rem;
            color: #71717a;
            text-decoration: none;
        }
        .forgot-password a:hover {
            color: #fe4f2a;
        }
        .forgot-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .forgot-modal.active {
            display: flex;
        }
        .forgot-modal-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 24rem;
            width: 100%;
            text-align: center;
        }
        .forgot-modal-content h3 {
            margin-bottom: 0.75rem;
            font-size: 1.125rem;
        }
        .forgot-modal-content p {
            color: #52525b;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1.25rem;
        }
        .forgot-modal-content a.email-link {
            color: #fe4f2a;
            text-decoration: none;
            font-weight: 600;
        }
        .forgot-modal-content button {
            padding: 0.5rem 1.5rem;
            background: #f4f4f5;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .login-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid #e4e4e7;
        }
        .login-tab {
            flex: 1;
            padding: 0.625rem;
            text-align: center;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            background: #fafafa;
            border: none;
            color: #71717a;
            transition: all 0.2s;
        }
        .login-tab.active {
            background: #18181b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="/assets/images/cms/agenci-logo-light.png" alt="Agenci" class="logo">
        </div>

        <?php if ($resetMode === 'reset' && isset($tokenValid) && $tokenValid): ?>
        <!-- ÅTERSTÄLL LÖSENORD -->
        <h2 style="font-size:1.25rem;margin-bottom:1.5rem;text-align:center;">Nytt lösenord</h2>
        <?php if ($resetError): ?>
            <div class="error"><?php echo htmlspecialchars($resetError); ?></div>
        <?php endif; ?>
        <form method="POST" action="/admin?action=reset">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
            <div class="form-group">
                <label class="label" for="new_password">Nytt lösenord</label>
                <input type="password" id="new_password" name="new_password" class="input" required minlength="8" placeholder="Minst 8 tecken">
            </div>
            <div class="form-group">
                <label class="label" for="new_password_confirm">Bekräfta lösenord</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" class="input" required minlength="8" placeholder="Upprepa lösenord">
            </div>
            <button type="submit" class="button">Spara lösenord</button>
        </form>
        <?php else: ?>
        <!-- VANLIG INLOGGNING -->
        <div class="login-tabs">
            <button type="button" class="login-tab active" onclick="switchTab('customer')">Kund</button>
            <button type="button" class="login-tab" onclick="switchTab('agency')">Agenci</button>
        </div>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="login_mode" id="login_mode" value="customer">

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
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>

            <div class="forgot-password" id="forgot-link">
                <a href="#" onclick="document.getElementById('forgot-modal').classList.add('active'); return false;">Glömt lösenord?</a>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <button type="submit" class="button">
                Logga in
            </button>
        </form>

        <?php if ($resetMode === 'forgot' || $resetSuccess || $resetError): ?>
        <div class="forgot-modal active" id="forgot-modal" onclick="if(event.target===this)this.classList.remove('active')">
            <div class="forgot-modal-content">
                <?php if ($resetSuccess): ?>
                    <h3>Skickat!</h3>
                    <p><?php echo htmlspecialchars($resetSuccess); ?></p>
                    <button onclick="window.location='/admin'">OK</button>
                <?php elseif ($resetMode === 'forgot'): ?>
                    <h3>Glömt lösenord?</h3>
                    <p>Ange din e-postadress så skickar vi en återställningslänk.</p>
                    <?php if ($resetError): ?>
                        <p style="color:#b91c1c;font-size:0.8125rem;"><?php echo htmlspecialchars($resetError); ?></p>
                    <?php endif; ?>
                    <form method="POST" action="/admin?action=forgot">
                        <?php echo csrf_field(); ?>
                        <input type="email" name="reset_email" class="input" placeholder="din@epost.se" required style="margin-bottom:1rem;">
                        <button type="submit" class="button" style="margin-bottom:0.75rem;">Skicka återställningslänk</button>
                    </form>
                    <button onclick="window.location='/admin'" style="background:none;border:none;color:#71717a;cursor:pointer;font-size:0.8125rem;">Tillbaka till inloggning</button>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="forgot-modal" id="forgot-modal" onclick="if(event.target===this)this.classList.remove('active')">
            <div class="forgot-modal-content">
                <h3>Glömt lösenord?</h3>
                <p>Ange din e-postadress så skickar vi en återställningslänk.</p>
                <form method="POST" action="/admin?action=forgot">
                    <?php echo csrf_field(); ?>
                    <input type="email" name="reset_email" class="input" placeholder="din@epost.se" required style="margin-bottom:1rem;">
                    <button type="submit" class="button" style="margin-bottom:0.75rem;">Skicka återställningslänk</button>
                </form>
                <button onclick="document.getElementById('forgot-modal').classList.remove('active')" style="background:none;border:none;color:#71717a;cursor:pointer;font-size:0.8125rem;">Avbryt</button>
            </div>
        </div>
        <?php endif; ?>

        <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeOn = document.getElementById('eye-icon');
            const eyeOff = document.getElementById('eye-off-icon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOn.style.display = 'none';
                eyeOff.style.display = 'block';
            } else {
                input.type = 'password';
                eyeOn.style.display = 'block';
                eyeOff.style.display = 'none';
            }
        }
        function switchTab(mode) {
            document.getElementById('login_mode').value = mode;
            document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            const forgotLink = document.getElementById('forgot-link');
            if (mode === 'agency') {
                forgotLink.style.display = 'none';
            } else {
                forgotLink.style.display = 'block';
            }
        }
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
