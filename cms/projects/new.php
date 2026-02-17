<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../security/session.php';
require_once __DIR__ . '/../../security/csrf.php';
require_once __DIR__ . '/../../security/validation.php';

// Inkludera helpers om den finns, annars definiera inline (bakåtkompatibilitet)
$helpersFile = __DIR__ . '/../helpers.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
}
if (!function_exists('convertToBytes')) {
    function convertToBytes(string $value): int {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $bytes = (int) $value;
        $bytes *= match ($unit) {
            'g' => 1024 * 1024 * 1024,
            'm' => 1024 * 1024,
            'k' => 1024,
            default => 1,
        };
        return $bytes;
    }
}
if (!function_exists('checkPostSizeLimit')) {
    function checkPostSizeLimit(): ?string {
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        $postMaxSize = ini_get('post_max_size');
        $postMaxBytes = convertToBytes($postMaxSize);
        if ($contentLength > $postMaxBytes || (empty($_POST) && $contentLength > 0)) {
            return 'Filen är för stor för servern. Max: ' . $postMaxSize;
        }
        return null;
    }
}
if (!function_exists('generateSlug')) {
    function generateSlug(string $title): string {
        $slug = strtolower($title);
        $slug = str_replace(['å', 'ä', 'ö'], ['a', 'a', 'o'], $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}

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
    $content = sanitize_rich_content($_POST['content'] ?? '');
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
            'content' => $content,
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
        .editor-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            padding: 0.5rem;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-radius: 0.75rem 0.75rem 0 0;
            background: #fafafa;
        }
        .editor-toolbar button {
            padding: 0.375rem 0.625rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            background: none;
            cursor: pointer;
            font-size: 0.875rem;
            color: #525252;
        }
        .editor-toolbar button:hover {
            background: #e5e5e5;
        }
        .editor-toolbar button.active {
            background: #e5e5e5;
            border-color: #d4d4d4;
        }
        .editor-toolbar .separator {
            width: 1px;
            background: #d4d4d4;
            margin: 0 0.25rem;
        }
        .editor-content {
            min-height: 12rem;
            padding: 1rem;
            border: 1px solid #d4d4d4;
            border-radius: 0 0 0.75rem 0.75rem;
            outline: none;
            font-family: inherit;
            font-size: 1rem;
            line-height: 1.6;
        }
        .editor-content:focus {
            border-color: #fe4f2a;
            box-shadow: 0 0 0 3px rgba(254, 79, 42, 0.1);
        }
        .editor-content a {
            color: #fe4f2a;
            text-decoration: underline;
        }
        .link-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .link-modal.active {
            display: flex;
        }
        .link-modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 24rem;
        }
        .link-modal-content h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .link-modal-content .form-group {
            margin-bottom: 1rem;
        }
        .link-modal-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .link-modal-actions button {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .link-modal-actions .btn-cancel {
            background: #e5e5e5;
            color: #18181b;
        }
        .link-modal-actions .btn-insert {
            background: #fe4f2a;
            color: white;
        }
        .preview-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        .preview-modal.active {
            display: block;
        }
        .preview-page {
            background: white;
            min-height: 100vh;
        }
        .preview-topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1.5rem;
            background: #18181b;
            color: white;
            font-size: 0.8125rem;
        }
        .preview-topbar span {
            opacity: 0.6;
        }
        .preview-topbar button {
            background: white;
            color: #18181b;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
        }
        .preview-article {
            padding: 4rem 1.5rem;
            max-width: 900px;
            margin: 0 auto;
        }
        .preview-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #737373;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }
        .preview-header {
            max-width: 800px;
            margin: 0 auto 3rem;
            text-align: center;
        }
        .preview-header .p-category {
            display: inline-block;
            padding: 0.375rem 1rem;
            background: #a78bfa;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .preview-header .p-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .preview-header .p-meta {
            font-size: 0.875rem;
            color: #737373;
        }
        .preview-cover {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 1rem;
            margin-bottom: 3rem;
        }
        .preview-inner {
            max-width: 800px;
            margin: 0 auto;
        }
        .preview-inner .p-summary {
            font-size: 1.25rem;
            color: #525252;
            line-height: 1.7;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e5e5e5;
        }
        .preview-inner .p-body {
            font-size: 1.0625rem;
            color: #18181b;
            line-height: 1.8;
        }
        .preview-inner .p-body p { margin-bottom: 1.5rem; }
        .preview-inner .p-body h2 { font-size: 1.5rem; font-weight: 600; margin-top: 2.5rem; margin-bottom: 1rem; }
        .preview-inner .p-body h3 { font-size: 1.25rem; font-weight: 600; margin-top: 2rem; margin-bottom: 0.75rem; }
        .preview-inner .p-body h4 { font-size: 1.125rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; }
        .preview-inner .p-body ul, .preview-inner .p-body ol { margin-bottom: 1.5rem; padding-left: 1.5rem; }
        .preview-inner .p-body li { margin-bottom: 0.5rem; }
        .preview-inner .p-body blockquote { border-left: 3px solid #e5e5e5; padding-left: 1rem; color: #737373; margin: 1.5rem 0; font-style: italic; }
        .preview-inner .p-body a { color: #8b5cf6; text-decoration: underline; }
        .preview-inner .p-body strong { font-weight: 600; }
        .btn-preview {
            padding: 0.875rem 1.5rem;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #d4d4d4;
            background: white;
            color: #525252;
            transition: all 0.2s;
        }
        .btn-preview:hover {
            background: #f5f5f5;
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
                    <label class="form-label">Innehåll</label>
                    <div class="editor-toolbar" id="editor-toolbar">
                        <button type="button" data-cmd="bold" title="Fet"><strong>B</strong></button>
                        <button type="button" data-cmd="italic" title="Kursiv"><em>I</em></button>
                        <button type="button" data-cmd="underline" title="Understruken"><u>U</u></button>
                        <div class="separator"></div>
                        <button type="button" data-cmd="formatBlock" data-value="H2" title="Rubrik 2">H2</button>
                        <button type="button" data-cmd="formatBlock" data-value="H3" title="Rubrik 3">H3</button>
                        <div class="separator"></div>
                        <button type="button" data-cmd="insertUnorderedList" title="Punktlista">&#8226; Lista</button>
                        <button type="button" data-cmd="insertOrderedList" title="Nummerlista">1. Lista</button>
                        <button type="button" data-cmd="formatBlock" data-value="BLOCKQUOTE" title="Citat">&#8220; Citat</button>
                        <div class="separator"></div>
                        <button type="button" id="btn-link" title="Infoga länk">&#128279; Länk</button>
                    </div>
                    <div class="editor-content" contenteditable="true" id="content-editor"><?php echo isset($content) ? $content : ''; ?></div>
                    <input type="hidden" name="content" id="content-hidden">
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
                    <button type="button" class="btn-preview" id="btn-preview">Förhandsgranska</button>
                    <a href="/cms/projects/" class="button button-secondary">Avbryt</a>
                </div>
            </form>
        </div>
    </div>
    </div>
    <!-- Preview-modal -->
    <div class="preview-modal" id="preview-modal">
        <div class="preview-page">
            <div class="preview-topbar">
                <span>Förhandsgranskning — så här ser inlägget ut live</span>
                <button type="button" id="preview-close">Stäng</button>
            </div>
            <article class="preview-article">
                <a href="#" class="preview-back" onclick="document.getElementById('preview-modal').classList.remove('active');return false;">&#8592; Tillbaka till projekt</a>
                <header class="preview-header">
                    <span class="p-category" id="preview-category"></span>
                    <h1 class="p-title" id="preview-title"></h1>
                    <div class="p-meta" id="preview-meta"></div>
                </header>
                <img class="preview-cover" id="preview-cover" style="display:none;" alt="">
                <div class="preview-inner">
                    <p class="p-summary" id="preview-summary"></p>
                    <div class="p-body" id="preview-content"></div>
                </div>
            </article>
        </div>
    </div>

    <!-- Länk-modal -->
    <div class="link-modal" id="link-modal">
        <div class="link-modal-content">
            <h3>Infoga länk</h3>
            <div class="form-group">
                <label class="form-label" for="link-url">URL</label>
                <input type="url" id="link-url" class="form-input" placeholder="https://example.com">
            </div>
            <div class="form-group">
                <label class="form-label" for="link-text">Länktext (valfritt)</label>
                <input type="text" id="link-text" class="form-input" placeholder="Klicka här">
            </div>
            <div class="link-modal-actions">
                <button type="button" class="btn-cancel" id="link-cancel">Avbryt</button>
                <button type="button" class="btn-insert" id="link-insert">Infoga</button>
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

    // Preview
    document.getElementById('btn-preview').addEventListener('click', function() {
        var modal = document.getElementById('preview-modal');
        document.getElementById('preview-title').textContent = document.getElementById('title').value || 'Ingen titel';
        var cat = document.getElementById('category').value;
        var catEl = document.getElementById('preview-category');
        if (cat) { catEl.textContent = cat; catEl.style.display = ''; } else { catEl.style.display = 'none'; }
        var summary = document.getElementById('summary').value;
        var sumEl = document.getElementById('preview-summary');
        if (summary) { sumEl.textContent = summary; sumEl.style.display = ''; } else { sumEl.style.display = 'none'; }
        document.getElementById('preview-content').innerHTML = document.getElementById('content-editor').innerHTML;
        // Datum
        var now = new Date();
        var months = ['januari','februari','mars','april','maj','juni','juli','augusti','september','oktober','november','december'];
        document.getElementById('preview-meta').textContent = now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        // Omslagsbild
        var coverInput = document.querySelector('input[name="cover_image"]');
        var coverImg = document.getElementById('preview-cover');
        if (coverInput && coverInput.files && coverInput.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { coverImg.src = e.target.result; coverImg.style.display = ''; };
            reader.readAsDataURL(coverInput.files[0]);
        } else {
            coverImg.style.display = 'none';
        }
        modal.classList.add('active');
        modal.scrollTop = 0;
    });
    document.getElementById('preview-close').addEventListener('click', function() {
        document.getElementById('preview-modal').classList.remove('active');
    });

    // Rich text editor
    (function() {
        const editor = document.getElementById('content-editor');
        const hidden = document.getElementById('content-hidden');
        const toolbar = document.getElementById('editor-toolbar');
        const linkModal = document.getElementById('link-modal');
        const linkUrl = document.getElementById('link-url');
        const linkText = document.getElementById('link-text');
        let savedSelection = null;

        function saveSelection() {
            const sel = window.getSelection();
            if (sel.rangeCount > 0) {
                savedSelection = sel.getRangeAt(0);
            }
        }

        function restoreSelection() {
            if (savedSelection) {
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(savedSelection);
            }
        }

        // Toolbar-knappar
        toolbar.addEventListener('click', function(e) {
            const btn = e.target.closest('button');
            if (!btn) return;
            e.preventDefault();

            if (btn.id === 'btn-link') {
                saveSelection();
                const sel = window.getSelection();
                linkText.value = sel.toString() || '';
                linkUrl.value = '';
                linkModal.classList.add('active');
                linkUrl.focus();
                return;
            }

            const cmd = btn.dataset.cmd;
            if (!cmd) return;

            editor.focus();
            if (cmd === 'formatBlock') {
                document.execCommand('formatBlock', false, btn.dataset.value);
            } else {
                document.execCommand(cmd, false, null);
            }
            updateToolbarState();
        });

        // Länk-modal
        document.getElementById('link-cancel').addEventListener('click', function() {
            linkModal.classList.remove('active');
        });

        document.getElementById('link-insert').addEventListener('click', function() {
            const url = linkUrl.value.trim();
            if (!url) return;

            linkModal.classList.remove('active');
            editor.focus();
            restoreSelection();

            const text = linkText.value.trim() || url;
            const sel = window.getSelection();

            if (sel.toString().length > 0) {
                document.execCommand('createLink', false, url);
            } else {
                const a = document.createElement('a');
                a.href = url;
                a.textContent = text;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';

                if (savedSelection) {
                    savedSelection.insertNode(a);
                    savedSelection.setStartAfter(a);
                    sel.removeAllRanges();
                    sel.addRange(savedSelection);
                }
            }
        });

        linkModal.addEventListener('click', function(e) {
            if (e.target === linkModal) {
                linkModal.classList.remove('active');
            }
        });

        // Toolbar active-state
        function updateToolbarState() {
            toolbar.querySelectorAll('button[data-cmd]').forEach(function(btn) {
                var cmd = btn.dataset.cmd;
                if (cmd === 'formatBlock') return;
                try {
                    btn.classList.toggle('active', document.queryCommandState(cmd));
                } catch(e) {}
            });
        }

        editor.addEventListener('keyup', updateToolbarState);
        editor.addEventListener('mouseup', updateToolbarState);

        // Synka editor-innehåll till hidden input vid submit
        editor.closest('form').addEventListener('submit', function() {
            hidden.value = editor.innerHTML;
        });
    })();
    </script>
</body>
</html>
