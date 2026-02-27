# Changelog

## 1.5.62
- Fix: Updater läser nu allowlist från ZIP:ens updater.php istället för in-memory konstanter (löser chicken-and-egg vid nya filer i UPDATABLE_FILES)
- Fix: Supportformuläret skapar tickets direkt via ticket_create() — kräver inte längre fungerande SMTP

## 1.5.61
- Fix: CSP är nu opt-in (aktiveras med ENABLE_CSP i config.php) för att inte blockera inline-scripts på befintliga sajter

## 1.5.60
- Revert: Återställde bug round 1+2 ändringar

## 1.5.59
- Fix: CSP nonce-hantering
- Fix: Scroll-animationer
- Fix: Mobilmeny
- Ny: Tickets/ärendehantering
- Ny: AI-agent

## 1.5.58
- Fix: Media library routing
- Fix: Image selector i CMS
