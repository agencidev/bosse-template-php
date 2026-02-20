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
$is_cms = preg_match('#^/(cms/|dashboard|admin|super-admin|seo|support|ai|projects|setup|api/|settings)#', $uri);
$is_frontend = !$is_cms;
?>
<div class="admin-bar">
    <div class="admin-bar__container">
        <div class="admin-bar__left">
            <a href="/dashboard" class="admin-bar__logo">
                <img src="/assets/images/cms/peys-logo-light.png" alt="PEYS" class="admin-bar__logo-img">
            </a>
            <?php if (is_super_admin()): ?>
                <span class="admin-bar__sa-badge" title="Super Admin"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2.5 19.5h19v2h-19v-2Zm19.57-9.36c-.22-.8-1.04-1.27-1.84-1.06L16.5 10.2l-3.12-5.4a1.5 1.5 0 0 0-2.59-.02L7.5 10.2l-3.73-1.12c-.8-.24-1.64.2-1.87 1-.12.4-.05.84.18 1.18L6 16.5h12l3.9-5.24c.25-.35.32-.78.17-1.12Z"/></svg></span>
            <?php endif; ?>
            <?php if ($is_frontend): ?>
                <div class="admin-bar__divider"></div>
                <button id="toggle-edit-mode" class="admin-bar__btn-edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    <span>Aktivera redigering</span>
                </button>
            <?php endif; ?>
        </div>

        <div class="admin-bar__right">
            <a href="/dashboard" class="admin-bar__nav-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>Dashboard</span>
            </a>
            <form method="get" action="/cms/admin.php" style="display:inline;margin:0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="admin-bar__nav-link admin-bar__nav-link--logout">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    <span>Logga ut</span>
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
    height: 3.5rem;
    background: #033234;
    color: white;
    font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    font-size: 0.875rem;
    line-height: 1.5;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.admin-bar *,
.admin-bar *::before,
.admin-bar *::after {
    font-family: inherit !important;
}

.admin-bar__container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
}

.admin-bar__left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.admin-bar__right {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.admin-bar__logo {
    display: flex;
    align-items: center;
    transition: opacity 0.2s;
}

.admin-bar__logo:hover {
    opacity: 0.8;
}

.admin-bar__logo-img {
    height: 1.625rem;
    width: auto;
}

.admin-bar__divider {
    width: 1px;
    height: 1.25rem;
    background: rgba(255,255,255,0.15);
}

/* Edit mode toggle button */
.admin-bar__btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.875rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,0.15);
    background: transparent;
    color: rgba(255,255,255,0.8);
    white-space: nowrap;
    transition: all 0.2s;
    line-height: 1;
}

.admin-bar__btn-edit:hover {
    background: rgba(255,255,255,0.08);
    color: white;
    border-color: rgba(255,255,255,0.25);
}

.admin-bar__btn-edit.active {
    background: #379b83;
    color: white;
    border-color: #379b83;
}

.admin-bar__btn-edit.active:hover {
    background: #2e8570;
    border-color: #2e8570;
}

.admin-bar__btn-edit svg {
    flex-shrink: 0;
}

/* Navigation links (Dashboard, Logga ut) */
.admin-bar__nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    background: transparent;
    color: rgba(255,255,255,0.55);
    white-space: nowrap;
    transition: all 0.15s;
    text-decoration: none;
    line-height: 1;
}

.admin-bar__nav-link:hover {
    color: white;
    background: rgba(255,255,255,0.08);
}

.admin-bar__nav-link svg {
    flex-shrink: 0;
    opacity: 0.7;
}

.admin-bar__nav-link:hover svg {
    opacity: 1;
}

.admin-bar__sa-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
    flex-shrink: 0;
}

body.has-admin-bar {
    padding-top: 3.5rem;
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
            toggleBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>Avsluta redigering</span>';
            document.body.classList.add('cms-edit-mode');
        } else {
            toggleBtn.classList.remove('active');
            toggleBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg><span>Aktivera redigering</span>';
            document.body.classList.remove('cms-edit-mode');
        }

        window.dispatchEvent(new CustomEvent('cms-edit-mode-changed', { detail: { isEditMode: window.CMS.isEditMode } }));
    });
}
</script>
