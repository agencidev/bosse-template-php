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

$success_message = '';
$error_message = '';

// Hantera bulk-åtgärder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    // Enskild radering
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $projects = array_filter($projects, fn($p) => $p['id'] !== $delete_id);
        file_put_contents($projects_file, json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        header('Location: /cms/projects/');
        exit;
    }

    // Bulk-åtgärder
    if (isset($_POST['bulk_action']) && isset($_POST['selected']) && is_array($_POST['selected'])) {
        $selected_ids = $_POST['selected'];
        $action = $_POST['bulk_action'];
        $affected = 0;

        switch ($action) {
            case 'delete':
                $original_count = count($projects);
                $projects = array_filter($projects, fn($p) => !in_array($p['id'], $selected_ids));
                $affected = $original_count - count($projects);
                $success_message = "{$affected} inlägg raderade";
                break;

            case 'publish':
                foreach ($projects as &$p) {
                    if (in_array($p['id'], $selected_ids) && ($p['status'] ?? 'draft') !== 'published') {
                        $p['status'] = 'published';
                        $affected++;
                    }
                }
                unset($p);
                $success_message = "{$affected} inlägg publicerade";
                break;

            case 'unpublish':
                foreach ($projects as &$p) {
                    if (in_array($p['id'], $selected_ids) && ($p['status'] ?? 'draft') === 'published') {
                        $p['status'] = 'draft';
                        $affected++;
                    }
                }
                unset($p);
                $success_message = "{$affected} inlägg avpublicerade";
                break;
        }

        file_put_contents($projects_file, json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inlägg - CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #033234;
            min-height: 100vh;
        }
        .page-content {
            padding: 3rem 1.5rem;
        }
        .container {
            max-width: 36rem;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: rgba(255,255,255,0.50);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: rgba(255,255,255,1.0);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: rgba(255,255,255,1.0);
        }
        .button-primary {
            background: #379b83;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-block;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
        }
        .empty-state p {
            color: rgba(255,255,255,0.50);
            margin-bottom: 1.5rem;
        }
        .projects-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .project-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: box-shadow 0.2s;
        }
        .project-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .project-info {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex: 1;
        }
        .project-image {
            width: 4rem;
            height: 4rem;
            border-radius: 0.75rem;
            object-fit: cover;
            background: linear-gradient(135deg, rgba(55, 155, 131, 0.2) 0%, rgba(55, 155, 131, 0.1) 100%);
        }
        .project-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.25rem;
        }
        .project-meta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.50);
        }
        .status-published {
            color: #16a34a;
        }
        .status-draft {
            color: #ca8a04;
        }
        .project-actions {
            display: flex;
            gap: 0.5rem;
        }
        .button {
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        .button-edit {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,1.0);
        }
        .button-edit:hover {
            background: rgba(255,255,255,0.15);
        }
        .button-delete {
            background: #fef2f2;
            color: #dc2626;
        }
        .button-delete:hover {
            background: #fee2e2;
        }
        .bulk-toolbar {
            display: none;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #054547;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }
        .bulk-toolbar.active {
            display: flex;
        }
        .bulk-toolbar .selected-count {
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .bulk-toolbar .bulk-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .bulk-btn-publish {
            background: #22c55e;
            color: white;
        }
        .bulk-btn-publish:hover {
            background: #16a34a;
        }
        .bulk-btn-unpublish {
            background: #f59e0b;
            color: white;
        }
        .bulk-btn-unpublish:hover {
            background: #d97706;
        }
        .bulk-btn-delete {
            background: #ef4444;
            color: white;
        }
        .bulk-btn-delete:hover {
            background: #dc2626;
        }
        .bulk-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
            accent-color: #379b83;
        }
        .select-all-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.08);
            border-radius: 0.75rem;
        }
        .select-all-container label {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.65);
            cursor: pointer;
        }
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/dashboard" class="back-link">&larr; Tillbaka</a>

        <div class="header">
            <h1 class="title">Inlägg</h1>
            <a href="/cms/projects/new" class="button-primary">+ Nytt inlägg</a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <p>Inga inlägg ännu</p>
                <a href="/cms/projects/new" class="button-primary">Skapa ditt första inlägg</a>
            </div>
        <?php else: ?>
            <form method="POST" id="bulk-form">
                <?php echo csrf_field(); ?>

                <!-- Bulk toolbar -->
                <div class="bulk-toolbar" id="bulk-toolbar">
                    <span class="selected-count"><span id="selected-count">0</span> valda</span>
                    <button type="submit" name="bulk_action" value="publish" class="bulk-btn bulk-btn-publish">Publicera</button>
                    <button type="submit" name="bulk_action" value="unpublish" class="bulk-btn bulk-btn-unpublish">Avpublicera</button>
                    <button type="submit" name="bulk_action" value="delete" class="bulk-btn bulk-btn-delete" onclick="return confirm('Är du säker på att du vill radera de valda inläggen?')">Radera</button>
                </div>

                <!-- Select all -->
                <div class="select-all-container">
                    <input type="checkbox" id="select-all" class="bulk-checkbox" onchange="toggleSelectAll(this)">
                    <label for="select-all">Välj alla</label>
                </div>

                <div class="projects-list">
                    <?php foreach ($projects as $project): ?>
                        <div class="project-card">
                            <input type="checkbox" name="selected[]" value="<?php echo htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'); ?>" class="bulk-checkbox project-checkbox" onchange="updateBulkToolbar()">
                            <div class="project-info">
                                <?php if (!empty($project['coverImage'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>" class="project-image">
                                <?php else: ?>
                                    <div class="project-image"></div>
                                <?php endif; ?>

                                <div class="project-details">
                                    <h3><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <div class="project-meta">
                                        <span><?php echo htmlspecialchars($project['category'] ?? 'Okategoriserad', ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span>&bull;</span>
                                        <span class="<?php echo ($project['status'] ?? 'draft') === 'published' ? 'status-published' : 'status-draft'; ?>">
                                            <?php echo ($project['status'] ?? 'draft') === 'published' ? 'Publicerad' : 'Utkast'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="project-actions">
                                <a href="/projects/edit?id=<?php echo urlencode($project['id']); ?>" class="button button-edit">Redigera</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>

            <!-- Enskild radering (separat formulär) -->
            <?php foreach ($projects as $project): ?>
            <form method="POST" id="delete-form-<?php echo htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'); ?>" style="display: none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'); ?>">
            </form>
            <?php endforeach; ?>

            <script>
            function toggleSelectAll(checkbox) {
                const checkboxes = document.querySelectorAll('.project-checkbox');
                checkboxes.forEach(cb => cb.checked = checkbox.checked);
                updateBulkToolbar();
            }

            function updateBulkToolbar() {
                const checkboxes = document.querySelectorAll('.project-checkbox:checked');
                const toolbar = document.getElementById('bulk-toolbar');
                const countSpan = document.getElementById('selected-count');
                const selectAll = document.getElementById('select-all');
                const allCheckboxes = document.querySelectorAll('.project-checkbox');

                countSpan.textContent = checkboxes.length;

                if (checkboxes.length > 0) {
                    toolbar.classList.add('active');
                } else {
                    toolbar.classList.remove('active');
                }

                // Uppdatera "välj alla" checkbox
                selectAll.checked = checkboxes.length === allCheckboxes.length && allCheckboxes.length > 0;
                selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
            }
            </script>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
