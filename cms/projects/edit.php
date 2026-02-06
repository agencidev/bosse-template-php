<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../security/session.php';
require_once __DIR__ . '/../../security/csrf.php';
require_once __DIR__ . '/../helpers.php';

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
        $coverImage = $projects[$projectIndex]['coverImage'] ?? '';
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
                    // Delete old cover image if exists
                    if (!empty($coverImage) && file_exists(__DIR__ . '/../../' . ltrim($coverImage, '/'))) {
                        @unlink(__DIR__ . '/../../' . ltrim($coverImage, '/'));
                    }
                    $coverImage = '/uploads/projects/' . $filename;
                }
            }
        }

        $projects[$projectIndex]['title'] = $title;
        $projects[$projectIndex]['slug'] = $slug;
        $projects[$projectIndex]['category'] = $category;
        $projects[$projectIndex]['summary'] = $summary;
        $projects[$projectIndex]['status'] = $status;
        $projects[$projectIndex]['coverImage'] = $coverImage;
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
        }
        .current-image {
            margin-bottom: 0.75rem;
        }
        .current-image img {
            max-height: 100px;
            border-radius: 0.5rem;
        }
        .current-image small {
            display: block;
            color: #a3a3a3;
            font-size: 0.75rem;
            margin-top: 0.25rem;
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
                    <label class="form-label">Omslagsbild</label>
                    <?php if (!empty($project['coverImage'])): ?>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>" alt="Nuvarande omslagsbild">
                        <small>Nuvarande bild — ladda upp ny för att ersätta</small>
                    </div>
                    <?php endif; ?>
                    <div class="file-upload" id="cover-upload">
                        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewImage(this)">
                        <div class="file-upload-text">
                            <strong>Välj bild</strong> eller dra hit
                            <br><small>JPG, PNG, WebP, GIF (max 5MB)</small>
                        </div>
                        <img class="image-preview" id="cover-preview" alt="Förhandsgranskning" style="display: none;">
                    </div>
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
