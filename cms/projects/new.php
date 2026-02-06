<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../security/session.php';
require_once __DIR__ . '/../../security/csrf.php';
require_once __DIR__ . '/../helpers.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Detektera om POST-data trunkerades pga PHP-gränser
    $postError = checkPostSizeLimit();
    if ($postError) {
        $error = $postError;
    } else {
        csrf_require();
    }

    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $status = $_POST['status'] ?? 'draft';

    if (empty($title)) {
        $error = 'Titel krävs';
    } elseif (empty($category)) {
        $error = 'Kategori krävs';
    } else {
        $slug = generateSlug($title);

        // Handle cover image upload
        $coverImage = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['cover_image']['tmp_name']);

            if (in_array($mime, $allowedMimes) && $_FILES['cover_image']['size'] <= $maxSize) {
                $uploadDir = __DIR__ . '/../../uploads/projects/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $filename = $slug . '-' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destPath)) {
                    $coverImage = '/uploads/projects/' . $filename;
                }
            }
        }

        $project = [
            'id' => uniqid(),
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'summary' => $summary,
            'status' => $status,
            'coverImage' => $coverImage,
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
        .file-upload {
            border: 2px dashed #d4d4d4;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .file-upload:hover {
            border-color: #fe4f2a;
            background: #fff7ed;
        }
        .file-upload input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-upload-text {
            font-size: 0.875rem;
            color: #737373;
        }
        .file-upload-text strong {
            color: #fe4f2a;
        }
        .image-preview {
            max-height: 120px;
            max-width: 100%;
            margin-top: 0.75rem;
            border-radius: 0.5rem;
            display: none;
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
            
            <form method="POST" enctype="multipart/form-data">
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
                    <label class="form-label">Omslagsbild</label>
                    <div class="file-upload" id="cover-upload">
                        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewImage(this)">
                        <div class="file-upload-text">
                            <strong>Välj bild</strong> eller dra hit
                            <br><small>JPG, PNG, WebP, GIF (max 5MB)</small>
                        </div>
                        <img class="image-preview" id="cover-preview" alt="Förhandsgranskning">
                    </div>
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
    <script>
    function previewImage(input) {
        const preview = document.getElementById('cover-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    }
    </script>
</body>
</html>
