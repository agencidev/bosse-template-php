<?php
require_once __DIR__ . '/../../bootstrap.php';

http_response_code(500);
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Serverfel | <?php echo SITE_NAME; ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        .error-content {
            max-width: 600px;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .error-description {
            font-size: 1.125rem;
            color: var(--color-neutral-600);
            margin-bottom: 2rem;
        }
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <div class="error-code">500</div>
            <h1 class="error-title">Serverfel</h1>
            <p class="error-description">
                Ett oväntat fel uppstod på servern. Vi arbetar på att lösa problemet.
            </p>
            <div class="error-actions">
                <a href="/" class="button button--primary">Gå till startsidan</a>
                <a href="javascript:location.reload()" class="button button--secondary">Försök igen</a>
            </div>
        </div>
    </div>
</body>
</html>
