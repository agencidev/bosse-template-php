<?php
require_once __DIR__ . '/../../config.example.php';
require_once __DIR__ . '/../../security/session.php';
require_once __DIR__ . '/../../security/csrf.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$error = '';
$success = false;

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
        
        $project = [
            'id' => uniqid(),
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'summary' => $summary,
            'status' => $status,
            'coverImage' => '',
            'gallery' => [],
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        $projects_file = __DIR__ . '/../../data/projects.json';
        $projects = [];
        
        if (file_exists($projects_file)) {
            $json = file_get_contents($projects_file);
            $projects = json_decode($json, true) ?? [];
        }
        
        $projects[] = $project;
        file_put_contents($projects_file, json_encode($projects, JSON_PRETTY_PRINT));
        
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
    <title>Nytt inlägg - CMS</title>
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/cms/projects/" class="back-link">← Tillbaka till inlägg</a>
        
        <h1 class="title">Nytt inlägg</h1>

        <div class="form-card">
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
                        value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Kategori *</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="">Välj kategori</option>
                        <option value="Projekt" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Projekt') ? 'selected' : ''; ?>>Projekt</option>
                        <option value="Blogg" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Blogg') ? 'selected' : ''; ?>>Blogg</option>
                        <option value="Nyhet" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Nyhet') ? 'selected' : ''; ?>>Nyhet</option>
                        <option value="Event" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Event') ? 'selected' : ''; ?>>Event</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="summary">Sammanfattning</label>
                    <textarea 
                        id="summary" 
                        name="summary" 
                        class="form-textarea"
                    ><?php echo isset($_POST['summary']) ? htmlspecialchars($_POST['summary'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="draft" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'selected' : ''; ?>>Utkast</option>
                        <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'selected' : ''; ?>>Publicerad</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">Skapa inlägg</button>
                    <a href="/cms/projects/" class="button button-secondary">Avbryt</a>
                </div>
            </form>
        </div>
    </div>
    </div>
</body>
</html>
