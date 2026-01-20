<?php
/**
 * Admin Bar Component
 * WordPress-liknande admin toolbar
 */

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    return;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<div class="cms-admin-bar">
    <div class="cms-admin-bar__logo">
        ⚡ CMS
    </div>
    
    <div class="cms-admin-bar__user">
        <span class="cms-admin-bar__username">
            Inloggad som: <strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong>
        </span>
        <a href="/cms/admin.php?action=logout" class="cms-admin-bar__logout">
            Logga ut
        </a>
    </div>
</div>

<script>
// Lägg till admin-mode class på body
document.body.classList.add('cms-admin-mode');
</script>
