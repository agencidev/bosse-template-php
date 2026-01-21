<?php
/**
 * Admin Bar Component
 * EXAKT som Next.js-versionen i Bosse Portal
 */

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    return;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<div class="fixed top-0 left-0 right-0 bg-woodsmoke text-white shadow-2xl z-[10000]">
    <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button onclick="window.location.href='/cms/dashboard.php'" class="hover:opacity-80 transition">
                <img src="/assets/images/logo-light.png" alt="agenci" style="height: 2rem;">
            </button>
            <div class="h-6 w-px bg-white/20"></div>
            <button
                id="toggle-edit-mode"
                class="px-4 py-1.5 rounded-md font-semibold text-sm transition bg-white/10 hover:bg-white/20"
            >
                ✏️ Aktivera redigering
            </button>
            <div id="edit-mode-indicator" class="hidden items-center gap-2">
                <span class="text-xs text-yellow-300 font-semibold animate-pulse">
                    🎨 Redigeringsläge aktivt
                </span>
                <span class="text-xs text-white/60">
                    • Klicka på text/bilder för att redigera
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                onclick="window.location.href='/cms/dashboard.php'"
                class="px-4 py-1.5 bg-white/10 hover:bg-white/20 rounded-md font-semibold text-sm transition"
            >
                Dashboard
            </button>
            <button
                onclick="window.location.href='/cms/admin.php?action=logout'"
                class="px-4 py-1.5 bg-white/10 hover:bg-white/20 rounded-md font-semibold text-sm transition"
            >
                Logga ut
            </button>
        </div>
    </div>
</div>

<div class="h-12" style="height: 48px"></div>

<script>
// CMS Edit Mode State - EXAKT som Next.js AdminProvider
window.CMS = window.CMS || {};
window.CMS.isEditMode = false;
window.CMS.isAuthenticated = true;

const toggleBtn = document.getElementById('toggle-edit-mode');
const indicator = document.getElementById('edit-mode-indicator');

toggleBtn.addEventListener('click', function() {
    window.CMS.isEditMode = !window.CMS.isEditMode;
    
    if (window.CMS.isEditMode) {
        toggleBtn.className = 'px-4 py-1.5 rounded-md font-semibold text-sm transition bg-persimmon text-white shadow-lg hover:bg-persimmon/90';
        toggleBtn.textContent = '✓ Avsluta redigering';
        indicator.classList.remove('hidden');
        indicator.classList.add('flex');
        document.body.classList.add('cms-edit-mode');
    } else {
        toggleBtn.className = 'px-4 py-1.5 rounded-md font-semibold text-sm transition bg-white/10 hover:bg-white/20';
        toggleBtn.textContent = '✏️ Aktivera redigering';
        indicator.classList.add('hidden');
        indicator.classList.remove('flex');
        document.body.classList.remove('cms-edit-mode');
    }
    
    // Trigger event för att uppdatera editable elements
    window.dispatchEvent(new CustomEvent('cms-edit-mode-changed', { detail: { isEditMode: window.CMS.isEditMode } }));
});
</script>
