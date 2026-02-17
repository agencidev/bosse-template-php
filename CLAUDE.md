# CLAUDE.md

Se `.rules/ai-rules.md` för fullständiga AI-regler och kodstandarder.
Se `.rules/brand-guide.md` för varumärkesguide (färger, typsnitt, tonalitet).

## ⛔ CORE-FILER — RÖR INTE!

Dessa filer/mappar tillhör Bosse-ramverket och skrivs över vid uppdatering:
`bootstrap.php`, `router.php`, `setup.php`, `cms/`, `security/`, `bin/`, `assets/css/variables.css`, `assets/css/components.css`, `assets/js/cms.js`

**Du får ändra:** `index.php` (startsidan), `pages/errors/` (felsidor), `assets/css/overrides.css`, `includes/header.php`, `includes/footer.php`, sidor i `pages/`, `cms/extensions/routes.php`, `data/content.json`, `data/projects.json`, `uploads/`

Se `.rules/ai-rules.md` → "CORE vs SAFE" för komplett lista.

## ⚠️ KRITISKT — Läs först!

**När du skapar inlägg/nyheter/event:**
- **ALDRIG** hårdkoda i PHP-filer (index.php etc.)
- **ALLTID** lägg till i `data/projects.json`

```json
{
  "id": "event-namn-2026",
  "title": "Event-namn",
  "slug": "event-namn",
  "category": "Event",
  "summary": "Kort beskrivning",
  "status": "published",
  "coverImage": "/uploads/bild.jpg",
  "createdAt": "2026-02-14 18:00:00"
}
```

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
<main>
    <!-- Innehåll -->
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
```

### CSS-ändringar
- **Skriv alltid i:** `assets/css/overrides.css`
- **Ändra ALDRIG:** `variables.css` eller `components.css`

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
- `/projekt` — Projekt-lista (`pages/projekt.php`)
- `/projekt/{slug}` — Enskilt projekt (`pages/projekt-single.php`)

### CMS-admin (kräver inloggning)
- `/admin` — Logga in
- `/dashboard` — Översikt
- `/projects` — Hantera inlägg
