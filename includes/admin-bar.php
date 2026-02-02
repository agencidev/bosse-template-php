<?php
/**
 * Admin Bar Component
 * Visar admin-bar när användaren är inloggad
 */

if (!is_logged_in()) {
    return;
}

// Check if we're on frontend (not in CMS/dashboard)
$uri = $_SERVER['REQUEST_URI'];
$is_cms = preg_match('#^/(cms/|dashboard|admin)#', $uri);
$is_frontend = !$is_cms;
$logo_src = '/assets/images/logo-dark.png';
?>
<div class="admin-bar admin-bar--dark">
    <div class="admin-bar__container">
        <div class="admin-bar__left">
            <a href="/dashboard" class="admin-bar__logo">
                <img src="<?php echo $logo_src; ?>" alt="<?php echo SITE_NAME; ?>">
            </a>
            <?php if (is_super_admin()): ?>
                <span class="admin-bar__sa-badge" title="Super Admin"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2.5 19.5h19v2h-19v-2Zm19.57-9.36c-.22-.8-1.04-1.27-1.84-1.06L16.5 10.2l-3.12-5.4a1.5 1.5 0 0 0-2.59-.02L7.5 10.2l-3.73-1.12c-.8-.24-1.64.2-1.87 1-.12.4-.05.84.18 1.18L6 16.5h12l3.9-5.24c.25-.35.32-.78.17-1.12Z"/></svg></span>
            <?php endif; ?>
            <?php if ($is_frontend): ?>
                <div class="admin-bar__divider"></div>
                <button id="toggle-edit-mode" class="admin-bar__button admin-bar__button--edit">
                    ✏️ Aktivera redigering
                </button>
            <?php endif; ?>
        </div>

        <div class="admin-bar__right">
            <a href="/dashboard" class="admin-bar__button">Dashboard</a>
            <form method="get" action="/cms/admin.php" style="display: inline; margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="admin-bar__button" style="background: none; border: none; font: inherit; cursor: pointer; padding: 0.5rem 1rem; border-radius: 9999px; background-color: rgba(255, 255, 255, 0.1); color: white; font-size: 0.875rem; font-weight: 600;">
                    Logga ut
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.admin-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 10000;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    height: 3rem;
    /* CMS-isolering: ignorera brand guide-variabler */
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
    font-size: 0.875rem;
    line-height: 1.5;
}

.admin-bar *,
.admin-bar *::before,
.admin-bar *::after {
    font-family: inherit !important;
}

.admin-bar--dark {
    background-color: #18181b;
    color: white;
}

.admin-bar__container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 2rem;
    height: 100%;
}

.admin-bar__left {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    max-width: 450px;
}

.admin-bar__right {
    display: flex;
    align-items: center;
    gap: 1rem;
    justify-self: end;
}

.admin-bar__logo {
    display: flex;
    align-items: center;
    transition: opacity 0.2s;
}

.admin-bar__logo:hover {
    opacity: 0.8;
}

.admin-bar__logo img {
    height: 1.5rem;
    width: auto;
}

.admin-bar__divider {
    width: 1px;
    height: 1.5rem;
    background-color: rgba(255, 255, 255, 0.2);
}

.admin-bar__button {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-block;
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    white-space: nowrap;
}

.admin-bar__button--edit.active {
    background: #fe4f2a;
    color: white;
}

.admin-bar__sa-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
    flex-shrink: 0;
}

body.has-admin-bar {
    padding-top: 3rem;
}
</style>

<script>
document.body.classList.add('has-admin-bar');

// CMS Edit Mode State
window.CMS = window.CMS || {};
window.CMS.isEditMode = false;
window.CMS.isAuthenticated = true;

const toggleBtn = document.getElementById('toggle-edit-mode');
if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        window.CMS.isEditMode = !window.CMS.isEditMode;
        
        if (window.CMS.isEditMode) {
            toggleBtn.classList.add('active');
            toggleBtn.textContent = '✓ Avsluta redigering';
            document.body.classList.add('cms-edit-mode');
        } else {
            toggleBtn.classList.remove('active');
            toggleBtn.textContent = '✏️ Aktivera redigering';
            document.body.classList.remove('cms-edit-mode');
        }
        
        window.dispatchEvent(new CustomEvent('cms-edit-mode-changed', { detail: { isEditMode: window.CMS.isEditMode } }));
    });
}
</script>
