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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_require();
    $delete_id = $_POST['delete_id'];
    $projects = array_filter($projects, fn($p) => $p['id'] !== $delete_id);
    file_put_contents($projects_file, json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    header('Location: /cms/projects/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inlägg - CMS</title>
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
            max-width: 64rem;
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: #18181b;
        }
        .button-primary {
            background: #fe4f2a;
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
            background: white;
            border-radius: 1.5rem;
            border: 1px solid #e5e5e5;
        }
        .empty-state p {
            color: #737373;
            margin-bottom: 1.5rem;
        }
        .projects-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .project-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e5e5e5;
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
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
        }
        .project-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 0.25rem;
        }
        .project-meta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            font-size: 0.875rem;
            color: #737373;
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
            background: #f5f5f5;
            color: #18181b;
        }
        .button-edit:hover {
            background: #e5e5e5;
        }
        .button-delete {
            background: #fef2f2;
            color: #dc2626;
        }
        .button-delete:hover {
            background: #fee2e2;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/dashboard" class="back-link">← Tillbaka</a>
        
        <div class="header">
            <h1 class="title">Inlägg</h1>
            <a href="/cms/projects/new" class="button-primary">+ Nytt inlägg</a>
        </div>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <p>Inga inlägg ännu</p>
                <a href="/cms/projects/new" class="button-primary">Skapa ditt första inlägg</a>
            </div>
        <?php else: ?>
            <div class="projects-list">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
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
                                    <span>•</span>
                                    <span class="<?php echo ($project['status'] ?? 'draft') === 'published' ? 'status-published' : 'status-draft'; ?>">
                                        <?php echo ($project['status'] ?? 'draft') === 'published' ? 'Publicerad' : 'Utkast'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="project-actions">
                            <a href="/projects/edit?id=<?php echo urlencode($project['id']); ?>" class="button button-edit">Redigera</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Är du säker på att du vill ta bort detta inlägg?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="button button-delete">Ta bort</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
