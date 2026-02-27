# Bosse Template — Projektöversikt & Changelog

> Detta dokument fungerar som projektminne. Det beskriver alla system, funktioner och flöden
> så att en ny AI-session (eller utvecklare) snabbt kan förstå hela projektet.

---

## Arkitektur

Bosse Template är ett PHP-baserat CMS-ramverk med automatisk uppdatering.
Ren PHP (inga ramverk), JSON-baserat innehåll, SQLite för tickets. Designat för
att deployas via FTP/Git till delade hostingar (SiteGround m.fl.).

```
Klient-sajt ←→ bosse-updates repo (manifest.json + ZIP)
                    ↑
              bosse-template (detta repo) → build-release.php → dist/
```

### Nyckelkonstanter (config.php)

| Konstant | Syfte |
|----------|-------|
| `ADMIN_USERNAME` / `ADMIN_PASSWORD_HASH` | CMS-inloggning (bcrypt) |
| `SITE_NAME`, `SITE_URL`, `CONTACT_EMAIL` | Sajt-metadata |
| `SMTP_HOST/PORT/USERNAME/PASSWORD` | E-post |
| `AGENCI_UPDATE_URL` | Update-server (raw GitHub URL) |
| `AGENCI_UPDATE_KEY` | HMAC-nyckel för signaturverifiering |
| `GITHUB_REPO` / `GITHUB_TOKEN` | Auto-push efter uppdatering |
| `ANTHROPIC_API_KEY` | AI-funktioner (Claude) |
| `ENABLE_CSP` | Aktivera Content Security Policy (opt-in) |

---

## System & Funktioner

### 1. Routing (`router.php`)

Front controller — alla requests går genom `router.php` via `.htaccess`.

**Statiska routes:**
- `/admin` → `cms/admin.php` (inloggning)
- `/dashboard` → `cms/dashboard.php`
- `/media` → `cms/media.php`
- `/projects`, `/projects/new`, `/projects/edit` → `cms/projects/`
- `/tickets` → `cms/tickets.php`
- `/tickets/{id}` → `cms/ticket-single.php`
- `/ai` → `cms/ai.php`
- `/seo` → `cms/seo.php`
- `/support` → `cms/support.php`
- `/super-admin` → `cms/super-admin.php`
- `/settings` → `cms/settings.php`
- `/setup` → `setup.php` (installationswizard)
- `/sitemap.xml` → `seo/sitemap.php`
- `/robots.txt` → `seo/robots.php`

**Dynamiska routes:**
- `/projekt/{slug}` → `pages/projekt-single.php` (hämtar från projects.json)
- Custom routes via `cms/extensions/routes.php` (överlever uppdateringar)
- Fallback: `pages/{slug}.php` → direkt PHP-fil

### 2. CMS & Innehåll

**Inline-redigering** — Admin ser redigerbara fält direkt på sajten:
- `editable_text($section, $field, $default, $tag, $class)` — renderar `<h2>`, `<p>` etc. med `contenteditable` för inloggade
- `editable_image($section, $field, $default, $alt, $class)` — renderar `<img>` med klickbar uppladdning
- Sparas i `data/content.json` via dot notation (`hero.title`, `about.text`)
- API: `cms/api.php` — `save_content`, `upload_image`, `delete_image`

**Projekt/Inlägg** — `data/projects.json`:
- Format: `{id, title, slug, category, summary, content, status, coverImage, gallery, createdAt}`
- Kategorier: Projekt, Blogg, Nyhet, Event
- Status: `published` (visas) eller `draft` (dold)
- Hanteras via `cms/projects/` (index, new, edit) med bulk-åtgärder
- Publikt: `/projekt` (lista) och `/projekt/{slug}` (detalj)

**Mediebibliotek** (`cms/media.php`):
- Listar alla filer i `uploads/`
- Visar: filnamn, URL, storlek, senast ändrad
- Referens-check: skannar content.json + projects.json
- Bildoptimering vid uppladdning: resize >2000px, JPEG-komprimering, WebP-kopia
- Dimensioner sparas i content.json (`key_width`, `key_height`) för CLS-prevention

### 3. Säkerhet

**Session** (`security/session.php`):
- HttpOnly + Secure + SameSite=Strict cookies
- 30 min inaktivitets-timeout, session-ID regenerering var 30:e minut
- Rate limiting: 5 inloggningsförsök per 5 min per IP
- Login-attempts loggas i `data/login_attempts.json` (hashade IP:n, GDPR)
- Lösenordsåterställning via e-post med 1h token

**CSRF** (`security/csrf.php`):
- Token i formulär via `csrf_field()` och verifiering via `csrf_require()`
- Stöd för JSON API via `X-CSRF-Token` header

**CSP** (`security/csp.php`):
- Nonce-baserad Content Security Policy (opt-in via `ENABLE_CSP`)
- Alla `<script>`-block kräver `<?php echo csp_nonce_attr(); ?>`
- Tillåter Google Analytics och Google Fonts
- Inga inline event handlers (`onclick` etc.) — kräver `addEventListener()`

**Super Admin** (`security/super-admin.php`):
- Tvånivå-autentisering: vanlig admin + super admin med separat token
- Super admin krävs för: uppdatering, SMTP-config, GitHub-config, tickets

**Validering** (`security/validation.php`):
- Input-sanitering och valideringshjälpare

### 4. Auto-uppdatering (`security/updater.php`)

**Komplett flöde:**
1. **Check** — Hämtar `manifest.json` från `AGENCI_UPDATE_URL` var 12:e timme
2. **Download** — Laddar ner ZIP med 60s timeout, verifierar HMAC-signatur
3. **Backup** — Kopierar alla UPDATABLE_FILES/DIRS till `data/backups/` (max 3 behålls)
4. **Apply** — Extraherar bara godkända filer (allowlist), path traversal-skydd
5. **Migrate** — Kör `_migrations/{version}.php` i versionsordning
6. **GitHub push** — Atomisk commit via GitHub Git Data API (text + binär)
7. **Cleanup** — Tar bort gamla backups och tmp-filer

**Chicken-and-egg fix (v1.5.62):**
Updater läser `UPDATABLE_FILES`/`UPDATABLE_DIRS` från ZIP:ens `security/updater.php`
istället för de i minnet, så att nya filer som lagts till i en release faktiskt extraheras.

**Filskydd:**
- `UPDATABLE_FILES` — exakta filer som får skrivas över (se `security/updater.php`)
- `UPDATABLE_DIRS` — `bin/`, `templates/`, `.github/`, `assets/images/cms/`
- `PROTECTED_FILES` — `config.php`, `.env`, `content.json`, `projects.json`, custom CSS, header/footer, index.php, felsidor
- `PROTECTED_DIRS` — `assets/images/`, `uploads/`

### 5. Tickets / Ärendehantering

**Databas** (`cms/tickets-db.php`):
- SQLite i `data/tickets.db` (WAL-mode)
- Schema: source, name, email, phone, subject, message, category, status, priority, ai_response, notes, timestamps
- Funktioner: `ticket_create()`, `ticket_get()`, `ticket_list()`, `ticket_update()`, `ticket_count_unresolved()`

**Admin** (`cms/tickets.php` + `cms/ticket-single.php`):
- Ärendelista med paginering (20/sida), filtrering på status/source/category
- Statusar: new, open, resolved, closed
- Kategorier: general_question, content_change, css_change, seo_change, bug_report, feature_request
- Färgkodade statusbadges

**Kontaktformulär** (`pages/kontakt.php`):
- Publikt formulär → skapar ticket med `source: 'contact'`

**Support** (`cms/support.php`):
- Adminformulär → skapar ticket direkt med `source: 'admin'` via `ticket_create()`
- Kräver INTE fungerande SMTP (sedan v1.5.62)

### 6. AI-funktioner

**AI Chat** (`cms/ai.php`):
- Chattgränssnitt för inloggade admins
- Streaming-svar med typing-indikator

**AI Agent** (`cms/ai-agent.php`):
- Ticket-resolution med Claude API (`ANTHROPIC_API_KEY`)
- Flöde: hämta ticket → bygg system prompt med sajt-kontext → anropa Claude → parsa svar → utför åtgärder
- Automatisk resolution vid confidence ≥ 0.7
- Kan triggas via cron: `/api?action=ai-resolve&cron_token={CRON_SECRET}`

### 7. SEO

- **Meta** (`seo/meta.php`) — `generateMeta()`: title, description, canonical, Open Graph, Twitter Card
- **Schema** (`seo/schema.php`) — Organization, Website, LocalBusiness JSON-LD
- **Sitemap** (`seo/sitemap.php`) — XML med statiska + dynamiska sidor + bilder
- **Robots** (`seo/robots.php`) — Dynamisk robots.txt

### 8. E-post (`includes/mailer.php`)

- Ren PHP SMTP-implementation (klass `SmtpMailer`, inga beroenden)
- Stöd för SSL (port 465) och STARTTLS (port 587)
- AUTH LOGIN-autentisering
- `send_mail($to, $subject, $body, $options)` — wrapper med HTML-stöd

### 9. Setup Wizard (`setup.php`)

3-stegs installation:
1. **Admin & SMTP** — användarnamn, lösenord, e-post, SMTP-config
2. **Sajt-metadata** — namn, beskrivning, URL, kontakt, sociala medier, öppettider
3. **Varumärke** — primär/sekundär/accent-färger med live-preview

Genererar `config.php`, `assets/css/variables.css` och sample-data.
Config-only mode: kan köras om för att ändra admin/SMTP utan att skriva över innehåll.

### 10. Build & Deploy

**Build** (`bin/build-release.php`):
```bash
php bin/build-release.php 1.5.62 [hmac-key]
```
1. Konkatenerar CSS (variables → reset → typography → components → cms → utilities → overrides)
2. Skapar `dist/releases/{version}.zip` med alla UPDATABLE-filer
3. Genererar `dist/manifest.json` (version, changelog, download URL, signatur, migrations)
4. Kopieras till `agencidev/bosse-updates` repo och pushas

**Deploy** (`.github/workflows/deploy-siteground.yml`):
- Trigger: push till `main`
- PHP syntax check → FTP-deploy till SiteGround

**Sync** (`bin/sync-to-template.sh`):
- Synkar test-projekt-ändringar tillbaka till template-repot

### 11. GitHub-integration (`security/updater.php` → `push_to_github()`)

Atomisk commit via GitHub Git Data API (5 steg):
1. Hämta senaste commit SHA för `main`
2. Hämta tree SHA
3. Bygg ny tree med ändrade filer (base64 för binärer)
4. Skapa commit: `chore: Uppdatera Bosse core-filer till v{version}`
5. Uppdatera branch-referens

### 12. Admin Bar (`includes/admin-bar.php`)

Fast toppbar för inloggade admins på frontend:
- Länkar: Dashboard, Media, Projekt
- Super admin-badge
- Frontend edit mode toggle
- Logga ut-knapp

---

## Filstruktur

```
/
├── index.php              # Startsida (SAFE — fri att ändra)
├── router.php             # Front controller (CORE)
├── bootstrap.php          # Miljösetup, autoload (CORE)
├── setup.php              # Installationswizard (CORE)
├── config.php             # Sajt-konfiguration (PROTECTED, gitignored)
├── bosse-health.php       # Health endpoint för portal-integration
├── CLAUDE.md              # AI-regler snabbref (CORE)
├── CHANGELOG.md           # Detta dokument
├── .windsurfrules         # AI-regler för Windsurf (CORE)
│
├── pages/                 # Publika undersidor
│   ├── kontakt.php        # Kontaktformulär → skapar ticket
│   ├── projekt.php        # Projektlista (CORE)
│   ├── projekt-single.php # Enskilt projekt (CORE)
│   ├── cookies.php        # Cookie-policy (SAFE)
│   ├── integritetspolicy.php # Integritetspolicy (SAFE)
│   └── errors/            # Felsidor 403/404/500 (SAFE)
│
├── cms/                   # Admin-system (CORE)
│   ├── admin.php          # Inloggning + lösenordsåterställning
│   ├── dashboard.php      # Översikt (stats, uppdateringar, systeminfo)
│   ├── content.php        # Innehållsfunktioner (editable_text/image)
│   ├── api.php            # REST API (save_content, upload, delete)
│   ├── api-super.php      # Super admin API
│   ├── helpers.php        # Hjälpfunktioner
│   ├── settings.php       # Sajt-inställningar (färger, SMTP, sociala medier)
│   ├── media.php          # Mediebibliotek
│   ├── seo.php            # SEO-inställningar
│   ├── support.php        # Supportformulär → ticket_create() direkt
│   ├── tickets.php        # Ärendelista med filtrering
│   ├── ticket-single.php  # Enskilt ärende
│   ├── tickets-db.php     # SQLite ticket-databas
│   ├── ai.php             # AI-chattgränssnitt
│   ├── ai-agent.php       # AI ticket-resolution (Claude API)
│   ├── super-admin.php    # Super admin dashboard
│   ├── projects/          # Inlägg-CRUD (index, new, edit)
│   └── extensions/
│       └── routes.php     # Custom routes (SAFE — överlever uppdateringar)
│
├── security/              # Säkerhetsmoduler (CORE)
│   ├── session.php        # Session, rate limiting, lösenordsåterställning
│   ├── csrf.php           # CSRF-tokens
│   ├── csp.php            # Content Security Policy (nonce)
│   ├── validation.php     # Input-validering
│   ├── updater.php        # Auto-uppdatering (check → download → backup → apply → push)
│   └── super-admin.php    # Super admin autentisering
│
├── seo/                   # SEO-verktyg (CORE)
│   ├── meta.php           # generateMeta() — title, OG, Twitter Card
│   ├── schema.php         # JSON-LD (Organization, Website, LocalBusiness)
│   ├── sitemap.php        # XML sitemap
│   └── robots.php         # Dynamisk robots.txt
│
├── includes/              # Delade komponenter
│   ├── header.php         # Global header (SAFE)
│   ├── footer.php         # Global footer (SAFE)
│   ├── fonts.php          # Font-laddning (SAFE)
│   ├── version.php        # BOSSE_VERSION konstant (CORE)
│   ├── admin-bar.php      # Admin-toolbar på frontend (CORE)
│   ├── cookie-consent.php # Cookie-samtycke (CORE)
│   ├── mailer.php         # SMTP-klient (CORE)
│   └── agenci-badge.php   # Powered by-badge (CORE)
│
├── assets/
│   ├── css/
│   │   ├── variables.css  # Design tokens — genereras av setup (PROTECTED)
│   │   ├── reset.css      # CSS reset (CORE)
│   │   ├── components.css # Baskomponenter (PROTECTED)
│   │   ├── cms.css        # CMS-styling (CORE)
│   │   ├── main.css       # Import-fil (CORE)
│   │   └── overrides.css  # Alla designändringar HÄR (SAFE)
│   ├── js/cms.js          # CMS JavaScript (CORE)
│   └── images/cms/        # CMS-logotyper (CORE)
│
├── data/                  # Runtime-data (gitignored delvis)
│   ├── content.json       # Sidinnehåll (PROTECTED)
│   ├── projects.json      # Inlägg/projekt (PROTECTED)
│   ├── tickets.db         # SQLite ärende-databas
│   ├── update-state.json  # Cachad uppdateringsstatus
│   ├── update-log.json    # Uppdateringshistorik (max 50 poster)
│   ├── login_attempts.json # Rate limiting
│   ├── backups/           # Auto-backups (max 3)
│   └── tmp/               # Temp-filer vid uppdatering
│
├── uploads/               # Användaruppladdningar (PROTECTED)
│
├── bin/                   # Build-scripts (CORE)
│   ├── build-release.php  # Bygger release-ZIP + manifest
│   ├── sync-to-template.sh # Synka till template-repo
│   └── generate-*.php     # Lösenords-/secret-generatorer
│
├── templates/             # Mallar för nya sidor (CORE)
├── _migrations/           # Versions-migrationer (inkluderas i ZIP)
├── dist/                  # Build-output (gitignored)
│   ├── manifest.json
│   └── releases/
│
└── .rules/
    ├── ai-rules.md        # Fullständiga AI/dev-regler
    └── brand-guide.md     # Varumärke (SAFE)
```

---

## Versionshistorik

### 1.5.62 (2026-02-27)
- **Fix: Updater chicken-and-egg** — `apply_update()` läser nu `UPDATABLE_FILES`/`UPDATABLE_DIRS` från ZIP:ens `security/updater.php` istället för de i minnet. Löser att nya filer (t.ex. `tickets-db.php`) hoppades över trots att de fanns i den nya releasens allowlist. Fallback till befintliga konstanter om ZIP:en saknar updater.
- **Fix: Support → Tickets direkt** — `cms/support.php` skapar tickets direkt via `ticket_create()` istället för att kräva fungerande SMTP. Borttaget: `require mailer.php` + `send_mail()`. Uppdaterade UI-texter ("ärende" istf "meddelande").

### 1.5.61 (2026-02-27)
- **Fix: CSP opt-in** — CSP-headern sätts nu bara om `ENABLE_CSP` är true i config. Löser att inline-scripts blockerades på sajter som inte anpassats för nonce-baserad CSP.

### 1.5.60 (2026-02-27)
- **Revert** — Återställde alla bug round 1+2 ändringar (1.5.58–1.5.59) p.g.a. regressioner.

### 1.5.59 (2026-02-27)
- **Ny: Tickets/ärendehantering** — SQLite-baserat ärendesystem (`cms/tickets-db.php`, `cms/tickets.php`, `cms/ticket-single.php`). Kontaktformuläret skapar tickets. Filtrering, paginering, statushantering.
- **Ny: AI-agent** — `cms/ai-agent.php` löser tickets automatiskt via Claude API. Kategorisering + åtgärder vid confidence ≥ 0.7.
- **Fix: CSP nonce** — Korrigerad nonce-generering och header-sättning.
- **Fix: Scroll-animationer** — Fixade trasiga scroll-baserade animationer.
- **Fix: Mobilmeny** — Hamburger-meny fungerade inte korrekt.

### 1.5.58 (2026-02-27)
- **Fix: Media library routing** — `/media` route fungerade inte i alla fall.
- **Fix: Image selector** — Bildväljaren i CMS-editorn hade bugg.

### 1.5.57
- **Ny: Mediebibliotek** — `cms/media.php` med dashboard, bilduppladdning, storleksinfo, referens-check.
- **Fix: Clean URL** — `/media` route för dashboard-länk.
- **Fix: Image selector** — Bugg i bildväljaren.

### 1.5.56
- **Fix:** Dold legacy CMS header-länk via admin-bar CSS.

### 1.5.55
- **Fix:** Bilduppladdning använde inte `field`-parametern för nested content keys.

### 1.5.54
- **Fix:** `pages/`-routes hade lägre prioritet än directories.
- **Ny:** Landing page-template tillagd.

### 1.5.53
- **Fix:** CSS cache — immutable cache-headers bröt @import i proxy-cachar.

### 1.5.52
- **Fix:** Front controller fallback i `.htaccess` för hostingar utan mod_rewrite.

### 1.5.51
- **Ny:** `outputDefaultSchemas()` i `seo/schema.php` — enklare att inkludera standard-schemas.

### 1.5.50
- **Ny: Nonce-baserad CSP** — `security/csp.php` med `csp_nonce_attr()`. Alla inline scripts kräver nonce.
- **Ny: Bildoptimering** — `optimize_image()` vid uppladdning (resize, komprimering, WebP).
- **Ny: Migrering-system** — `_migrations/{version}.php` körs automatiskt vid uppdatering.
- **Fix:** Config-sparning misslyckades tyst i super-admin.
- **Fix:** SMTP fallback i super-admin.
- **Fix:** Fatal error när `security/csp.php` saknades på äldre sajter.
- CMS-knapp borttagen från public header.

### 1.5.45–1.5.49
- **Ny: Health endpoint** — `bosse-health.php` för portal-integration.
- **Ny: `.site-url`** — Synk med Agenci-portalen.
- **Fix:** SMTP-port sparades inte (integer regex mismatch).
- **Fix:** SMTP/GitHub-inställningar sparades inte i super-admin.
- **Fix:** GitHub push-loggning.
- **Ny:** GitHub push-status visas i super-admin uppdateringslogg.

### 1.5.40–1.5.44
- **Ny: PEYS-rebrand** — Dark teal theme, pill-knappar, nytt logo.
- **Fix:** Token UX-indikator, felloggar rensade.
- **Fix:** GitHub-inställningar sparades inte.

### 1.5.35–1.5.39
- **Fix:** `BOSSE_VERSION` undefined vid ny installation.
- **Fix:** Tålig version-laddning i bootstrap.
- **Fix:** Undersidor syntes inte efter migrering (pages/ fallback i router).
- **Fix:** `.htaccess` pages/-fallback för Apache/SiteGround.

### 1.5.30–1.5.34
- **Ny:** Heading-hierarki-regler i AI-regler.
- **Fix:** CORE/SAFE-bugfixar (SEO→CORE, .windsurfrules updatable, error pages protected).
- Borttaget: oanvänt `.env`-ekosystem.
- **Ny:** Grön uppdateringsknapp, gitignore-fix.
- **Ny:** Brand-filer pushas till GitHub efter setup.

### 1.5.28–1.5.29
- **Ny: Rich editor** med preview i content editor.
- **Ny: Config editor** i super-admin.
- **Ny: Lösenordsåterställning** via e-post.
- **Ny: Agenci-inlogg** (portal SSO).
- **Ny: Säker deploy** utan config-wipe.

### 1.5.27
- **Ny: GitHub-push vid uppdatering** — Automatisk commit via Git Data API.

### 1.5.23–1.5.26
- **Ny: Extension-stöd** — `cms/extensions/routes.php` för custom routes/API/dashboard widgets.
- **Ny: Config-only mode** vid re-klon.
- **Fix:** Session-hantering, cache-busting, Vary-header.
- **Fix:** CSP: tillåt Google Fonts + GA + update-server.
- **Fix:** SiteGround `.htaccess`-kompatibilitet.

### 1.5.12–1.5.22
- **Ny: Bildoptimering** vid uppladdning.
- **Ny: Bulk-hantering** av projekt.
- **Ny: SEO-editor** i CMS.
- **Ny: Backup-system** med rollback.
- **Fix:** PHP 8.5-kompatibilitet.
- **Fix:** Bildförhandsgranskning.
- **Ny:** Sidmall (`templates/page-template.php`).

### 1.5.0–1.5.11
- **Ny:** Komplett CMS med inloggning, dashboard, inställningar, SMTP.
- **Ny:** Favicon-stöd.
- **Ny:** Agenci-logo separation.
- **Ny:** Auto-uppdateringssystem med signaturverifiering.
- Setup wizard (3 steg: admin, metadata, varumärke).

### 1.4.x
- Dashboard visar inlägg + redigerbara kategorier.
- AI-regler: inlägg ALLTID i projects.json.
- ÖÄÅ-fix i slugs.

### 1.1.0
- Super Admin, auto-uppdatering, komplett CMS-template.
- Uppdaterings-UI i super-admin.

### 1.0.0
- Initial release: PHP template med CMS, SEO, säkerhet.
- WordPress-liknande inline-redigering.
- Kontaktformulär, cookie-consent, router.
