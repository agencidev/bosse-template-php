<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../security/session.php';
require_once __DIR__ . '/../../security/csrf.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$projects_file = __DIR__ . '/../../data/projects.json';
$projects = [];

if (file_exists($projects_file)) {
    $json = file_get_contents($projects_file);
    $projects = json_decode($json, true) ?? [];
}

// Hitta projektet via ID
$id = $_GET['id'] ?? '';
$project = null;
$projectIndex = null;

foreach ($projects as $i => $p) {
    if ($p['id'] === $id) {
        $project = $p;
        $projectIndex = $i;
        break;
    }
}

if ($project === null) {
    header('Location: /cms/projects/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $status = $_POST['status'] ?? 'draft';

    if (empty($title)) {
        $error = 'Titel krävs';
    } elseif (empty($category)) {
        $error = 'Kategori krävs';
    } else {
        $slug = strtolower($title);
        $slug = str_replace(['å', 'ä', 'ö'], ['a', 'a', 'o'], $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $projects[$projectIndex]['title'] = $title;
        $projects[$projectIndex]['slug'] = $slug;
        $projects[$projectIndex]['category'] = $category;
        $projects[$projectIndex]['summary'] = $summary;
        $projects[$projectIndex]['status'] = $status;
        $projects[$projectIndex]['updatedAt'] = date('Y-m-d H:i:s');

        file_put_contents($projects_file, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        header('Location: /cms/projects/');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redigera inlägg - CMS</title>
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
            max-width: 42rem;
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
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid #d4d4d4;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #fe4f2a;
            box-shadow: 0 0 0 3px rgba(254, 79, 42, 0.1);
        }
        .form-textarea {
            resize: none;
            min-height: 6rem;
        }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .form-actions {
            display: flex;
            gap: 0.75rem;
            padding-top: 1rem;
        }
        .button {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            text-align: center;
        }
        .button-primary {
            background: #fe4f2a;
            color: white;
        }
        .button-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .button-secondary {
            background: #e5e5e5;
            color: #18181b;
        }
        .meta-info {
            font-size: 0.8125rem;
            color: #a3a3a3;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f5f5f5;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/cms/projects/" class="back-link">&larr; Tillbaka till inlägg</a>

        <h1 class="title">Redigera inlägg</h1>

        <div class="form-card">
            <div class="meta-info">
                Skapad: <?php echo htmlspecialchars($project['createdAt'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($project['updatedAt'])): ?>
                 &bull; Uppdaterad: <?php echo htmlspecialchars($project['updatedAt'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label" for="title">Titel *</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-input"
                        required
                        value="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Kategori *</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="">Välj kategori</option>
                        <?php
                        $categories = ['Projekt', 'Blogg', 'Nyhet', 'Event'];
                        foreach ($categories as $cat):
                        ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($project['category'] ?? '') === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="summary">Sammanfattning</label>
                    <textarea
                        id="summary"
                        name="summary"
                        class="form-textarea"
                    ><?php echo htmlspecialchars($project['summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="draft" <?php echo ($project['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Utkast</option>
                        <option value="published" <?php echo ($project['status'] ?? 'draft') === 'published' ? 'selected' : ''; ?>>Publicerad</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">Spara ändringar</button>
                    <a href="/cms/projects/" class="button button-secondary">Avbryt</a>
                </div>
            </form>
        </div>
    </div>
    </div>
</body>
</html>
