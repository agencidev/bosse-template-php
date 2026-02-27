# Changelog

## 1.5.63
- Fix: "Aktivera redigering" visades på /tickets och /media
- Fix: Lösenordsbyte via super-admin fungerade inte (regex saknade semikolon)
- Fix: triggerAI-knappen kraschade (undefined event)
- Fix: Upload-ikon felplacerad i bildväljaren
- Ny: Ögon-ikon för lösenordsfält i setup + super-admin
- Ändrad: Container 48rem på support/seo/settings
- Ändrad: Enhetlig tillbaka-knapp på media-sidan
- Borttagen: AI-chattsida (ai-agent backend kvar)
- Borttagen: Meta-formulär från SEO-sidan

## 1.5.62
- Fix: Updater läser allowlist från ZIP:ens updater.php (löser chicken-and-egg vid nya filer)
- Fix: Support skapar tickets direkt via ticket_create() — inget SMTP krävs

## 1.5.61
- Fix: CSP opt-in (aktiveras med ENABLE_CSP) — blockerade inline-scripts på befintliga sajter

## 1.5.60
- Revert: Återställde bug round 1+2 ändringar

## 1.5.59
- Ny: Tickets/ärendehantering (SQLite, kontaktformulär → tickets)
- Ny: AI-agent för ticket-resolution (Claude API)
- Fix: CSP nonce, scroll-animationer, mobilmeny

## 1.5.58
- Fix: Media library routing, image selector

## 1.5.57
- Ny: Mediebibliotek med dashboard och referens-check

## 1.5.50
- Ny: Nonce-baserad CSP (csp_nonce_attr)
- Ny: Bildoptimering vid uppladdning
- Ny: Migrering-system (_migrations/)

## 1.5.45
- Ny: Health endpoint (bosse-health.php)
- Ny: Portal-integration (.site-url)

## 1.5.42
- Ny: PEYS-rebrand (dark teal theme)

## 1.5.29
- Ny: Rich editor, config editor, lösenordsåterställning

## 1.5.27
- Ny: GitHub-push vid uppdatering (Git Data API)

## 1.5.23
- Ny: Extension-stöd (cms/extensions/routes.php)

## 1.5.12
- Ny: Bildoptimering, bulk-hantering, SEO-editor, backup-system

## 1.5.0
- Ny: Komplett CMS, SMTP, setup wizard, auto-uppdatering

## 1.0.0
- Initial release: PHP template med CMS, SEO, säkerhet, inline-redigering
