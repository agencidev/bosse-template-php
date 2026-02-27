<?php
/**
 * Tickets — Admin ticket list
 * Dark theme matching dashboard (#033234, DM Sans)
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/tickets-db.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$superAdmin = is_super_admin();

// Filters (super admin only)
$statusFilter = $superAdmin ? ($_GET['status'] ?? '') : '';
$sourceFilter = $superAdmin ? ($_GET['source'] ?? '') : '';
$categoryFilter = $superAdmin ? ($_GET['category'] ?? '') : '';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($sourceFilter) $filters['source'] = $sourceFilter;
if ($categoryFilter) $filters['category'] = $categoryFilter;

$result = ticket_list($filters, $perPage, $offset);
$tickets = $result['tickets'];
$total = $result['total'];
$totalPages = max(1, (int) ceil($total / $perPage));

$statusLabels = [
    'new' => 'Nytt',
    'in_progress' => 'Pågående',
    'resolved' => 'Löst',
    'closed' => 'Stängt',
];
$statusColors = [
    'new' => '#3b82f6',
    'in_progress' => '#f59e0b',
    'resolved' => '#22c55e',
    'closed' => '#6b7280',
];
$sourceLabels = [
    'contact' => 'Kontakt',
    'admin' => 'Admin',
];
$categoryLabels = [
    'general_question' => 'Allmän fråga',
    'content_change' => 'Innehållsändring',
    'css_change' => 'Designändring',
    'seo_change' => 'SEO',
    'bug_report' => 'Bugg',
    'feature_request' => 'Önskemål',
];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ärenden - CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #033234;
            min-height: 100vh;
            color: rgba(255,255,255,1.0);
        }
        .page-content { padding: 3rem 1.5rem; }
        .container { max-width: 48rem; margin: 0 auto; }
        .back-link {
            display: inline-block;
            color: rgba(255,255,255,0.50);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: rgba(255,255,255,1.0); }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .title { font-size: 2rem; font-weight: bold; }
        .total-badge {
            background: rgba(255,255,255,0.10);
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }
        .filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filters select {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 0.5rem;
            color: white;
            padding: 0.5rem 0.75rem;
            font-size: 0.8125rem;
            font-family: inherit;
            cursor: pointer;
        }
        .filters select option { background: #033234; color: white; }
        .ticket-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .ticket-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 0.75rem;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
        }
        .ticket-row:hover { background: rgba(255,255,255,0.08); }
        .ticket-subject {
            flex: 1;
            font-size: 0.9375rem;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ticket-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        .badge-status {
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            white-space: nowrap;
        }
        .badge-source {
            font-size: 0.625rem;
            font-weight: 600;
            padding: 0.15rem 0.4rem;
            border-radius: 9999px;
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.5);
        }
        .badge-category {
            font-size: 0.625rem;
            padding: 0.15rem 0.4rem;
            border-radius: 9999px;
            background: rgba(55,155,131,0.15);
            color: #379b83;
        }
        .ticket-date {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.40);
            white-space: nowrap;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1.5rem;
            color: rgba(255,255,255,0.4);
        }
        .empty-state svg { margin-bottom: 1rem; opacity: 0.4; }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        .pagination a, .pagination span {
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            text-decoration: none;
            color: rgba(255,255,255,0.6);
        }
        .pagination a:hover { background: rgba(255,255,255,0.08); }
        .pagination .active {
            background: #379b83;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/dashboard" class="back-link">&larr; Tillbaka</a>

        <div class="page-header">
            <h1 class="title">Ärenden</h1>
            <span class="total-badge"><?php echo $total; ?> totalt</span>
        </div>

        <?php if ($superAdmin): ?>
        <form class="filters" method="GET" action="/tickets">
            <select name="status" onchange="this.form.submit()">
                <option value="">Alla statusar</option>
                <?php foreach ($statusLabels as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $statusFilter === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="source" onchange="this.form.submit()">
                <option value="">Alla källor</option>
                <?php foreach ($sourceLabels as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $sourceFilter === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category" onchange="this.form.submit()">
                <option value="">Alla kategorier</option>
                <?php foreach ($categoryLabels as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $categoryFilter === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" />
            </svg>
            <p>Inga ärenden hittades.</p>
        </div>
        <?php else: ?>
        <div class="ticket-list">
            <?php foreach ($tickets as $ticket):
                $status = $ticket['status'] ?? 'new';
                $color = $statusColors[$status] ?? '#6b7280';
            ?>
            <a href="/tickets/<?php echo (int) $ticket['id']; ?>" class="ticket-row">
                <span class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></span>
                <div class="ticket-meta">
                    <?php if ($superAdmin && !empty($ticket['source'])): ?>
                    <span class="badge-source"><?php echo htmlspecialchars($sourceLabels[$ticket['source']] ?? $ticket['source']); ?></span>
                    <?php endif; ?>
                    <?php if ($superAdmin && !empty($ticket['category'])): ?>
                    <span class="badge-category"><?php echo htmlspecialchars($categoryLabels[$ticket['category']] ?? $ticket['category']); ?></span>
                    <?php endif; ?>
                    <span class="badge-status" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                        <?php echo htmlspecialchars($statusLabels[$status] ?? $status); ?>
                    </span>
                    <span class="ticket-date"><?php echo htmlspecialchars(substr($ticket['created_at'] ?? '', 0, 10)); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryBase = [];
            if ($statusFilter) $queryBase['status'] = $statusFilter;
            if ($sourceFilter) $queryBase['source'] = $sourceFilter;
            if ($categoryFilter) $queryBase['category'] = $categoryFilter;

            for ($i = 1; $i <= $totalPages; $i++):
                $queryBase['page'] = $i;
                $url = '/tickets?' . http_build_query($queryBase);
            ?>
                <?php if ($i === $page): ?>
                <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="<?php echo htmlspecialchars($url); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
