# CLAUDE.md

Se `.rules/ai-rules.md` för fullständiga AI-regler och kodstandarder.
Se `.rules/brand-guide.md` för varumärkesguide (färger, typsnitt, tonalitet).

## ⛔ CORE-FILER — RÖR INTE!

Dessa filer/mappar tillhör Bosse-ramverket och skrivs över vid uppdatering:
`bootstrap.php`, `router.php`, `setup.php`, `bosse-health.php`, `cms/`, `security/`, `bin/`, `seo/`, `includes/admin-bar.php`, `includes/cookie-consent.php`, `includes/mailer.php`, `assets/css/variables.css`, `assets/css/components.css`, `assets/js/cms.js`

**Du får ändra:** `index.php` (startsidan), `pages/errors/` (felsidor), `assets/css/overrides.css`, `assets/css/projekt-custom.css`, `assets/css/projekt-single-custom.css`, `includes/header.php`, `includes/footer.php`, sidor i `pages/`, `cms/extensions/routes.php`, `data/content.json`, `data/projects.json`, `uploads/`

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
  "category": "Projekt|Blogg|Nyhet|Event",
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
- `slug` MÅSTE vara unik — den blir URL:en (`/projekt/min-slug` eller `/blogg/min-slug`)
- `id` MÅSTE vara unik — används av CMS:et för redigering
- `category` styr vilken URL inlägget visas på: `"Projekt"` → `/projekt`, `"Blogg"` → `/blogg`
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
- **Egen design för projekt/blogg:** Skapa `assets/css/projekt-custom.css` (listvy) eller `assets/css/projekt-single-custom.css` (enskild vy) — ersätter default-styles helt, överlever uppdateringar

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
  "category": "Projekt|Blogg|Nyhet|Event",
  "summary": "Kort beskrivning",
  "content": "Fullständig text",
  "status": "published|draft",
  "coverImage": "/uploads/bild.jpg",
  "createdAt": "2026-02-04 12:00:00"
}
```

### Publika sidor
- `/` — Huvudsida (`index.php` i rot)
- `/kontakt` — Kontaktformulär (`pages/kontakt.php`)
- `/projekt` — Projekt med kategori "Projekt" (`pages/projekt.php`)
- `/projekt/{slug}` — Enskilt projekt (`pages/projekt-single.php`)
- `/blogg` — Inlägg med kategori "Blogg" (via routes)
- `/blogg/{slug}` — Enskilt blogginlägg (via routes)
- `/nyheter` — Inlägg med kategori "Nyhet" (via routes)
- `/nyheter/{slug}` — Enskild nyhet (via routes)
- `/event` — Inlägg med kategori "Event" (via routes)
- `/event/{slug}` — Enskilt event (via routes)

**Routing:** Alla kategori-URL:er utom `/projekt` routas via `cms/extensions/routes.php` → front-controller i `index.php`. Samma PHP-filer (`pages/projekt.php` + `pages/projekt-single.php`), kontextväxling via URL-prefix.

### CMS-admin (kräver inloggning)
- `/admin` — Logga in
- `/dashboard` — Översikt
- `/projects` — Hantera inlägg
- `/tickets` — Ärendehantering
- `/support` — Skapa supportärende (skapar ticket direkt, inget SMTP krävs)
