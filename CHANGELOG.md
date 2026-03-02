# Changelog

## 1.5.67
- Fix: GitHub-push failade om GITHUB_REPO sparades som full URL (normaliseras nu till org/name)
- Fix: GITHUB_REPO normaliseras vid sparning i super-admin (tar bort https://github.com/ och .git)

## 1.5.66
- Fix: Self-heal — updater laddar ner och extraherar saknade core-filer automatiskt
- Fix: Projekt som uppdaterades från gamla versioner saknade cms/media.php, tickets, ai-agent

## 1.5.65
- Fix: Cookie-banner kontrast (#b3b3b3 → #d4d4d4) — uppfyller WCAG AA 4.5:1
- Fix: Lösenordshash korrumperades av preg_replace (admin.php + api-super.php)
- Ny: Relaterade inlägg på projekt-singelsidan (samma kategori, max 3)
- Ny: Lösenordsbyte i CMS-inställningar (/settings)
- Ny: Bulk-optimering av bilder i mediabiblioteket

## 1.5.64
- Ny: Drag & drop i bildväljaren (utöver klick-upload)
- Fix: Bilder visades dubbelt i mediabiblioteket (WebP-kopior filtreras)
- Ändrad: Inläggssidan — bredare container (48rem), moderna bulk-knappar, pill-badges, dark theme

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
