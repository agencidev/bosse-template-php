<?php
/**
 * Agenci Badge - Visas i nedre vänstra hörnet
 */
?>
<div class="agenci-badge">
    <a href="https://agenci.se" target="_blank" rel="noopener noreferrer">
        <span class="agenci-badge__text">Skapad av oss 🥳</span>
    </a>
</div>

<style>
.agenci-badge {
    position: fixed;
    bottom: 1.5rem;
    left: 1.5rem;
    z-index: 9999;
}

.agenci-badge a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    text-decoration: none;
    transition: all 0.2s;
}

.agenci-badge a:hover {
    transform: translateY(-2px);
}

.agenci-badge__text {
    font-size: 0.875rem;
    color: #18181b;
    font-weight: 600;
}
</style>
