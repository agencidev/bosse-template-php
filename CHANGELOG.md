# Changelog - bosse-template-php

Alla ändringar från bolaget-projektet som har synkats till template.

## [2026-01-21] - Cookie Policy & Bugfixar

### ✨ Nya Funktioner

#### Cookie Consent Banner med Google Consent Mode v2
- **Mörk design** som Cookiebot (#2d2d2d bakgrund)
- **Tre knappar** i banner:
  - "Acceptera alla" - Accepterar alla cookies
  - "Endast nödvändiga" - Endast nödvändiga cookies
  - "Hantera inställningar" - Öppnar modal
- **Modal med inställningar**:
  - Expanderbara kategorier (Nödvändiga, Analytiska, Funktionella, Marknadsföring)
  - Toggle-switches för varje kategori
  - "Mer information" sektion
  - Tre knappar: Acceptera alla, Endast nödvändiga, Spara inställningar
- **Google Consent Mode v2** integration
- **Automatisk företagsinfo** från config.php
- **Cookie sparas** i 365 dagar
- **Fil**: `includes/cookie-consent.php`
- **Inkluderad i**: `index.php` (rad 106)

#### Onboarding Workflow
- **Komplett checklista** för nya projekt
- **Steg-för-steg guide** för setup
- **Deployment instruktioner**
- **Fil**: `.windsurf/workflows/onboarding.md`
- **Användning**: `/onboarding` i Windsurf

#### Automatiska Tester
- **PHP CLI test-script** för projektverifiering
- **Kontrollerar**:
  - Filstruktur
  - Config-filer
  - Säkerhetsinställningar
  - Databas-anslutning
  - Skrivbehörigheter
- **Färgkodad output** (grön/röd/gul)
- **Fil**: `bin/test-setup.php`
- **Användning**: `php bin/test-setup.php`

#### CSRF Secret Generator
- **Genererar säkra secrets** för CSRF-skydd
- **Fil**: `bin/generate-secret.php`
- **Användning**: `php bin/generate-secret.php`

#### Sync Script
- **Automatisk synkning** från test-projekt till template
- **Exkluderar**: .git, uploads, content.json, .env
- **Fil**: `bin/sync-to-template.sh`
- **Användning**: `./bin/sync-to-template.sh`

### 🐛 Bugfixar

#### Logout-funktionalitet
- **Problem**: Admin bar försvann inte efter logout
- **Lösning**: 
  - Lagt till no-cache headers
  - Ändrat logout från GET till POST-form
  - Säkerställt att session rensas helt
  - Redirect till /admin efter logout
- **Filer**: `cms/admin.php`, `includes/admin-bar.php`

#### URL Routing
- **Problem**: Clean URLs fungerade inte med PHP:s inbyggda server
- **Lösning**:
  - Skapat `router.php` för PHP:s inbyggda server
  - Uppdaterat `.htaccess` för Apache
  - Stöd för /admin, /dashboard, och alla CMS-sidor utan .php
- **Filer**: `router.php`, `.htaccess`

#### Admin Bar Konsistens
- **Problem**: Admin bar ändrade bredd beroende på innehåll
- **Lösning**:
  - Bytt från flexbox till CSS grid
  - Fasta bredder på vänster och höger sektioner
  - Konsistent höjd (3rem) på alla sidor
- **Fil**: `includes/admin-bar.php`

#### Admin Bar Synlighet
- **Problem**: "Aktivera redigering" visades på fel sidor
- **Lösning**:
  - Förbättrad URI-regex för is_frontend check
  - Fungerar nu korrekt med clean URLs
- **Fil**: `includes/admin-bar.php`

### 🎨 Design-förbättringar

#### Login-sida
- **Borttaget**: Top banner
- **Ändrat**: Bakgrundsfärg från blå gradient till ljusgrå
- **Fil**: `cms/admin.php`

#### CTA-färg
- **Uppdaterat**: Alla knappar använder nu #fe4f2a (Agenci orange)
- **Konsistent**: Samma färg på hela sajten
- **Filer**: `assets/css/components.css`, diverse PHP-filer

#### Agenci Badge
- **Lagt till**: "Powered by Agenci" badge i footer
- **Design**: Minimalistisk, diskret
- **Fil**: `includes/agenci-badge.php`

### 📝 Dokumentation

#### README.md
- **Uppdaterat**: Installation och setup-instruktioner
- **Lagt till**: Cookie policy information
- **Lagt till**: Testing instruktioner

#### SYNC_WORKFLOW.md
- **Nytt**: Guide för synkning mellan projekt och template
- **Beskriver**: Hur man använder sync-scriptet

### 🔧 Tekniska Förbättringar

#### Session Management
- **Förbättrat**: Session-hantering vid logout
- **Lagt till**: Extra säkerhetskontroller
- **Fil**: `security/session.php`

#### Cache Headers
- **Lagt till**: No-cache headers på admin-sidor
- **Förhindrar**: Caching av admin bar efter logout
- **Filer**: `cms/admin.php`, `index.php`

#### Clean URLs
- **Implementerat**: Rena URLer utan .php-extension
- **Stöd för**: Både Apache och PHP:s inbyggda server
- **Filer**: `.htaccess`, `router.php`

## Sammanfattning av Ändringar

### Nya Filer (11 st)
1. `includes/cookie-consent.php` - Cookie banner med Google Consent Mode
2. `includes/agenci-badge.php` - Agenci badge
3. `router.php` - URL router för PHP:s inbyggda server
4. `bin/test-setup.php` - Automatiska tester
5. `bin/generate-secret.php` - CSRF secret generator
6. `bin/sync-to-template.sh` - Sync script
7. `.windsurf/workflows/onboarding.md` - Onboarding checklista
8. `CHANGELOG.md` - Denna fil
9. `SYNC_WORKFLOW.md` - Sync guide

### Modifierade Filer (8 st)
1. `cms/admin.php` - Logout fix, design-ändringar
2. `includes/admin-bar.php` - Konsistens, synlighet, logout-form
3. `includes/header.php` - CTA-färg
4. `includes/footer.php` - Agenci badge
5. `index.php` - Cookie consent, cache headers
6. `.htaccess` - Clean URLs
7. `README.md` - Uppdaterad dokumentation
8. `assets/css/components.css` - CTA-färg

### Bugfixar (5 st)
1. ✅ Logout fungerar 100%
2. ✅ Admin bar konsistent bredd och höjd
3. ✅ "Aktivera redigering" visas på rätt sidor
4. ✅ Clean URLs fungerar med PHP:s inbyggda server
5. ✅ Login-sida utan top banner och blå bakgrund

### Nya Funktioner (4 st)
1. ✅ Cookie consent banner med Google Consent Mode v2
2. ✅ Onboarding workflow för nya projekt
3. ✅ Automatiska tester för projektverifiering
4. ✅ Agenci badge i footer

## Testning

### Manuell Testning
```bash
# Starta server
php -S localhost:8001 router.php

# Testa i browser:
# 1. Cookie banner dyker upp i nedre vänstra hörnet
# 2. Alla tre knappar fungerar
# 3. Modal öppnas med inställningar
# 4. Logout fungerar och redirectar till /admin
# 5. Admin bar har konsistent bredd och höjd
# 6. Clean URLs fungerar (/admin, /dashboard, etc.)
```

### Automatisk Testning
```bash
# Kör alla tester
php bin/test-setup.php

# Förväntat resultat: Alla tester gröna
```

## Deployment

### För nya projekt från template:
1. Klona template
2. Kör `/onboarding` workflow i Windsurf
3. Följ steg-för-steg instruktionerna
4. Kör `php bin/test-setup.php` för verifiering

### För befintliga projekt:
1. Synka ändringar med `./bin/sync-to-template.sh`
2. Testa lokalt
3. Pusha till GitHub

## Nästa Steg

- [ ] Testa på staging-miljö
- [ ] Verifiera Google Consent Mode integration
- [ ] Testa cookie banner på mobil
- [ ] Dokumentera cookie policy för kunder
- [ ] Skapa video-guide för onboarding

---

**Skapad**: 2026-01-21  
**Projekt**: bolaget → bosse-template-php  
**Utvecklare**: Christian Hagler + Windsurf Cascade
