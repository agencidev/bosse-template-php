# CLAUDE.md

Se `.rules/ai-rules.md` för fullständiga AI-regler och kodstandarder.
Se `.rules/brand-guide.md` för varumärkesguide (färger, typsnitt, tonalitet).

## ⛔ CORE-FILER — RÖR INTE!

Dessa filer/mappar tillhör Bosse-ramverket och skrivs över vid uppdatering:
`bootstrap.php`, `router.php`, `setup.php`, `bosse-health.php`, `cms/`, `security/`, `bin/`, `seo/`, `includes/admin-bar.php`, `includes/cookie-consent.php`, `includes/mailer.php`, `assets/css/variables.css`, `assets/css/components.css`, `assets/js/cms.js`

**Du får ändra:** `index.php` (startsidan), `pages/errors/` (felsidor), `assets/css/overrides.css`, `assets/css/inlagg-custom.css`, `assets/css/inlagg-single-custom.css`, `includes/header.php`, `includes/footer.php`, sidor i `pages/`, `cms/extensions/routes.php`, `data/content.json`, `data/projects.json`, `uploads/`

Se `.rules/ai-rules.md` → "CORE vs SAFE" för komplett lista.

## ⚠️ KRITISKT — Läs först!

### STOPP! Inlägg/nyheter/event/projekt — MÅSTE gå via `data/projects.json`

**ALDRIG** skapa inlägg genom att:
- Hårdkoda HTML/PHP i `index.php` eller andra PHP-filer
- Skapa nya PHP-filer för enskilda inlägg
- Lägga innehåll i `data/content.json` (det är för sidinnehåll, INTE inlägg)

**ALLTID** följ dessa steg exakt:

1. **Läs** `data/projects.json` med Read-verktyget
2. **Lägg till** ett nytt objekt i arrayen med ALLA obligatoriska fält (se nedan)
3. **Skriv tillbaka** hela arrayen till `data/projects.json`
4. **Verifiera** att filen är giltig JSON (`php -r "json_decode(file_get_contents('data/projects.json')) ?: exit(1);"`)

**Obligatoriska fält** (saknas något visas inlägget INTE korrekt):
```json
{
  "id": "unikt-id-2026",
  "title": "Titel på inlägget",
  "slug": "url-vänlig-slug",
  "category": "Inlägg",
  "summary": "Kort beskrivning (visas i listvy)",
  "content": "Fullständig text (HTML tillåtet)",
  "status": "published",
  "coverImage": "/uploads/bild.jpg",
  "gallery": [],
  "createdAt": "2026-03-02 12:00:00"
}
```

**Viktigt:**
- `status` MÅSTE vara `"published"` för att synas publikt
- `slug` MÅSTE vara unik — den blir URL:en (`/inlagg/min-slug`)
- `id` MÅSTE vara unik — används av CMS:et för redigering
- `category` styr vilken URL inlägget visas på: `"Inlägg"` → `/inlagg`. Extra kategorier konfigureras per projekt (se "Kategori- och innehållssidor" nedan)
- Inlägget syns automatiskt i CMS-admin (`/projects`) och publikt — ingen extra konfiguration behövs

## Snabbref

### Nya sidor (VIKTIGT!)
När du skapar nya sidor (om-oss.php, tjanster.php etc.):
1. **Skapa filen i `pages/`-mappen** (t.ex. `pages/om-oss.php`)
2. **Kopiera** `templates/page-template.php` som bas
3. **MÅSTE inkludera:** `header.php` och `footer.php` med `__DIR__ . '/../'`-prefix
4. **Lägg till rutt** i `cms/extensions/routes.php`

```php
// Minsta struktur för ny sida i pages/:
<?php include __DIR__ . '/../includes/admin-bar.php'; ?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main id="main-content">
    <!-- Innehåll -->
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
```

### CSS-ändringar
- **Skriv alltid i:** `assets/css/overrides.css`
- **Ändra ALDRIG:** `variables.css` eller `components.css`
- **Egen design för inlägg:** Skapa `assets/css/inlagg-custom.css` (listvy) eller `assets/css/inlagg-single-custom.css` (enskild vy) — ersätter default-styles helt, överlever uppdateringar

### Innehåll
- **Sidinnehåll:** `data/content.json`
- **Inlägg/projekt:** `data/projects.json` (status: `"published"` för att synas)

### Nya sektioner
Använd ALLTID `editable_text()` och `editable_image()` för redigerbart innehåll:

```php
<?php editable_text('sektion', 'titel', 'Standardrubrik', 'h2', 'css-klass'); ?>
<?php editable_text('sektion', 'text', 'Standardtext', 'p', 'css-klass'); ?>
<?php editable_image('sektion', 'bild', '/assets/images/placeholder.jpg', 'Alt-text', 'css-klass'); ?>
```

### Projekt/Inlägg-format
```json
{
  "id": "unikt-id",
  "title": "Titel",
  "slug": "url-slug",
  "category": "Inlägg",
  "summary": "Kort beskrivning",
  "content": "Fullständig text",
  "status": "published|draft",
  "coverImage": "/uploads/bild.jpg",
  "createdAt": "2026-02-04 12:00:00"
}
```

### Kategori- och innehållssidor

Bosse har ett inbyggt system för att skapa kategorisidor (blogg, nyheter, event, portfolio etc.). Alla kategorier använder samma PHP-filer (`pages/inlagg.php` + `pages/inlagg-single.php`) men med olika URL-prefix och filter.

**Default:** `/inlagg` finns alltid (hanteras av `.htaccess`). Extra kategorier konfigureras per projekt genom att redigera **2 filer**:

#### Steg 1: `cms/extensions/categories.php` — Definiera kategorin
```php
return [
    '/inlagg' => ['category' => 'Inlägg', 'title_sv' => 'Inlägg', 'title_en' => 'Posts', 'base_url' => '/inlagg'],
    '/happenings' => ['category' => 'Event', 'title_sv' => 'Event', 'title_en' => 'Events', 'base_url' => '/happenings'],
    '/blogg' => ['category' => 'Blogg', 'title_sv' => 'Blogg', 'title_en' => 'Blog', 'base_url' => '/blogg'],
];
```

#### Steg 2: `cms/extensions/routes.php` — Lägg till routes
```php
return [
    '/happenings' => '/pages/inlagg.php',
    '/blogg' => '/pages/inlagg.php',
    '__patterns' => [
        ['/^\/happenings\/([a-z0-9-]+)$/', '/pages/inlagg-single.php', ['slug']],
        ['/^\/blogg\/([a-z0-9-]+)$/', '/pages/inlagg-single.php', ['slug']],
    ],
];
```

#### Resultat
- Listvy: `/happenings` visar alla inlägg med `"category": "Event"`
- Enskild: `/happenings/mitt-event` visar detalj
- CMS-dropdown i "Skapa inlägg" uppdateras automatiskt med nya kategorier

#### Exempelprompts som triggar detta
- "Skapa en eventsida som heter happenings" → skapar `/happenings` + `/happenings/{slug}`
- "Lägg till kategorierna Event och Nyhet" → lägger till båda i categories.php + routes.php
- "Jag vill ha en blogg på /blogg och portfolio på /projekt" → skapar båda
- "Ta bort kategorin Blogg" → tar bort från categories.php + routes.php

**Viktigt:** `category`-värdet i `categories.php` MÅSTE matcha `category`-fältet i `data/projects.json`. Exakt match, case-sensitive.

### Publika sidor
- `/` — Huvudsida (`index.php` i rot)
- `/kontakt` — Kontaktformulär (`pages/kontakt.php`)
- `/inlagg` — Alla inlägg (`pages/inlagg.php`)
- `/inlagg/{slug}` — Enskilt inlägg (`pages/inlagg-single.php`)
- Extra kategorisidor (t.ex. `/blogg`, `/happenings`) konfigureras per projekt (se ovan)

**Routing:** `/inlagg` hanteras av `.htaccess`. Extra kategorisidor routas via `cms/extensions/routes.php`. Samma PHP-filer, kontextväxling via URL-prefix.

### CMS-admin (kräver inloggning)
- `/admin` — Logga in
- `/dashboard` — Översikt
- `/projects` — Hantera inlägg
- `/tickets` — Ärendehantering
- `/support` — Skapa supportärende (skapar ticket direkt, inget SMTP krävs)
