<?php
/**
 * Ticket Single — View/manage individual ticket
 * Super admin sees AI response, actions, admin notes, status controls
 * Regular admin sees subject, message, status (read-only)
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/tickets-db.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$superAdmin = is_super_admin();
$ticketId = (int) ($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    header('Location: /tickets');
    exit;
}

$ticket = ticket_get($ticketId);
if (!$ticket) {
    http_response_code(404);
    echo 'Ärende hittades inte.';
    exit;
}

// Handle POST actions (super admin only)
$updateMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $superAdmin) {
    csrf_require();
    $action = $_POST['_action'] ?? '';

    if ($action === 'update') {
        $updates = [];
        if (isset($_POST['status'])) {
            $allowed = ['new', 'in_progress', 'resolved', 'closed'];
            if (in_array($_POST['status'], $allowed, true)) {
                $updates['status'] = $_POST['status'];
            }
        }
        if (isset($_POST['admin_notes'])) {
            $updates['admin_notes'] = trim($_POST['admin_notes']);
        }
        if (isset($_POST['category'])) {
            $updates['category'] = $_POST['category'];
        }
        if (!empty($updates)) {
            ticket_update($ticketId, $updates);
            $ticket = ticket_get($ticketId);
            $updateMsg = 'Ärendet uppdaterat.';
        }
    }
}

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
$categoryLabels = [
    'general_question' => 'Allmän fråga',
    'content_change' => 'Innehållsändring',
    'css_change' => 'Designändring',
    'seo_change' => 'SEO',
    'bug_report' => 'Bugg',
    'feature_request' => 'Önskemål',
];

$status = $ticket['status'] ?? 'new';
$statusColor = $statusColors[$status] ?? '#6b7280';
$aiActions = !empty($ticket['ai_actions']) ? json_decode($ticket['ai_actions'], true) : [];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ärende #<?php echo $ticketId; ?> - CMS</title>
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
        .container { max-width: 42rem; margin: 0 auto; }
        .back-link {
            display: inline-block;
            color: rgba(255,255,255,0.50);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: rgba(255,255,255,1.0); }
        .ticket-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .ticket-id {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.35);
        }
        .badge-status {
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
        }
        .title { font-size: 1.75rem; font-weight: bold; margin-bottom: 1.5rem; }
        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .card-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255,255,255,0.45);
            margin-bottom: 0.75rem;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        .meta-item label {
            display: block;
            font-size: 0.6875rem;
            color: rgba(255,255,255,0.4);
            margin-bottom: 0.125rem;
        }
        .meta-item span {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.85);
        }
        .message-body {
            font-size: 0.9375rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.8);
            white-space: pre-wrap;
        }
        .ai-response-text {
            font-size: 0.9375rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.8);
            white-space: pre-wrap;
        }
        .ai-action {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .ai-action-type {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #379b83;
            margin-bottom: 0.25rem;
        }
        .ai-action-diff {
            font-size: 0.8125rem;
            font-family: monospace;
            color: rgba(255,255,255,0.6);
        }
        .ai-action-diff .before { color: #f87171; }
        .ai-action-diff .after { color: #34d399; }
        .form-group { margin-bottom: 1rem; }
        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            margin-bottom: 0.375rem;
        }
        .form-select, .form-textarea {
            width: 100%;
            padding: 0.625rem 0.75rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 0.5rem;
            color: white;
            font-size: 0.875rem;
            font-family: inherit;
        }
        .form-select option { background: #033234; }
        .form-textarea { resize: vertical; min-height: 6rem; }
        .btn-row {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        .btn-primary { background: #379b83; color: white; }
        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .btn-ai {
            background: rgba(139,92,246,0.15);
            color: #a78bfa;
            border: 1px solid rgba(139,92,246,0.3);
        }
        .btn:hover { opacity: 0.85; }
        .alert-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            color: #22c55e;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 640px) {
            .meta-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/tickets" class="back-link">&larr; Alla ärenden</a>

        <?php if ($updateMsg): ?>
        <div class="alert-success"><?php echo htmlspecialchars($updateMsg); ?></div>
        <?php endif; ?>

        <div class="ticket-header">
            <span class="ticket-id">#<?php echo $ticketId; ?></span>
            <span class="badge-status" style="background: <?php echo $statusColor; ?>20; color: <?php echo $statusColor; ?>;">
                <?php echo htmlspecialchars($statusLabels[$status] ?? $status); ?>
            </span>
        </div>

        <h1 class="title"><?php echo htmlspecialchars($ticket['subject']); ?></h1>

        <!-- Meta info -->
        <div class="card">
            <div class="card-title">Information</div>
            <div class="meta-grid">
                <?php if (!empty($ticket['name'])): ?>
                <div class="meta-item">
                    <label>Namn</label>
                    <span><?php echo htmlspecialchars($ticket['name']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($ticket['email'])): ?>
                <div class="meta-item">
                    <label>E-post</label>
                    <span><?php echo htmlspecialchars($ticket['email']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($ticket['phone'])): ?>
                <div class="meta-item">
                    <label>Telefon</label>
                    <span><?php echo htmlspecialchars($ticket['phone']); ?></span>
                </div>
                <?php endif; ?>
                <div class="meta-item">
                    <label>Skapad</label>
                    <span><?php echo htmlspecialchars($ticket['created_at'] ?? ''); ?></span>
                </div>
                <div class="meta-item">
                    <label>Källa</label>
                    <span><?php echo htmlspecialchars($ticket['source'] === 'admin' ? 'Admin-support' : 'Kontaktformulär'); ?></span>
                </div>
                <div class="meta-item">
                    <label>Kategori</label>
                    <span><?php echo htmlspecialchars($categoryLabels[$ticket['category'] ?? ''] ?? ($ticket['category'] ?? 'Ej kategoriserad')); ?></span>
                </div>
            </div>
        </div>

        <!-- Message -->
        <div class="card">
            <div class="card-title">Meddelande</div>
            <div class="message-body"><?php echo htmlspecialchars($ticket['message']); ?></div>
        </div>

        <?php if ($superAdmin): ?>

        <!-- AI Response -->
        <?php if (!empty($ticket['ai_response'])): ?>
        <div class="card">
            <div class="card-title">AI-svar</div>
            <div class="ai-response-text"><?php echo htmlspecialchars($ticket['ai_response']); ?></div>
            <?php if ($ticket['ai_resolved_at']): ?>
            <div style="margin-top: 0.75rem; font-size: 0.75rem; color: rgba(255,255,255,0.35);">
                AI kördes: <?php echo htmlspecialchars($ticket['ai_resolved_at']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- AI Actions log -->
        <?php if (!empty($aiActions)): ?>
        <div class="card">
            <div class="card-title">AI-åtgärder</div>
            <?php foreach ($aiActions as $action): ?>
            <div class="ai-action">
                <div class="ai-action-type"><?php echo htmlspecialchars($action['type'] ?? 'unknown'); ?></div>
                <div class="ai-action-diff">
                    <?php if (!empty($action['before'])): ?>
                    <div class="before">- <?php echo htmlspecialchars(mb_substr($action['before'], 0, 200)); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($action['after'])): ?>
                    <div class="after">+ <?php echo htmlspecialchars(mb_substr($action['after'], 0, 200)); ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($action['timestamp'])): ?>
                <div style="font-size: 0.6875rem; color: rgba(255,255,255,0.3); margin-top: 0.25rem;"><?php echo htmlspecialchars($action['timestamp']); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Admin controls -->
        <div class="card">
            <div class="card-title">Hantera ärende</div>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="_action" value="update">

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statusLabels as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $status === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categoryLabels as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($ticket['category'] ?? '') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Admin-anteckningar</label>
                    <textarea name="admin_notes" class="form-textarea" placeholder="Interna anteckningar..."><?php echo htmlspecialchars($ticket['admin_notes'] ?? ''); ?></textarea>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn-primary">Spara ändringar</button>

                    <?php if (!empty($ticket['email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($ticket['email']); ?>?subject=Re: <?php echo rawurlencode($ticket['subject']); ?>" class="btn btn-secondary">
                        Svara via email
                    </a>
                    <?php endif; ?>

                    <button type="button" class="btn btn-ai" onclick="triggerAI(event, <?php echo $ticketId; ?>)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        Låt AI hantera
                    </button>
                </div>
            </form>
        </div>

        <?php endif; // superAdmin ?>
    </div>
    </div>

    <?php if ($superAdmin): ?>
    <script <?php echo csp_nonce_attr(); ?>>
    function triggerAI(e, ticketId) {
        if (!confirm('Vill du att AI:n analyserar och försöker lösa detta ärende?')) return;
        var btn = e.target.closest('.btn-ai');
        btn.textContent = 'AI jobbar...';
        btn.disabled = true;
        fetch('/cms/api.php?action=ai-resolve&ticket_id=' + ticketId, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?php echo csrf_token(); ?>'},
            body: JSON.stringify({_csrf: '<?php echo csrf_token(); ?>'})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert('Fel: ' + (data.error || 'Okänt fel'));
                btn.textContent = 'Låt AI hantera';
                btn.disabled = false;
            }
        })
        .catch(function() {
            alert('Nätverksfel');
            btn.textContent = 'Låt AI hantera';
            btn.disabled = false;
        });
    }
    </script>
    <?php endif; ?>
</body>
</html>
