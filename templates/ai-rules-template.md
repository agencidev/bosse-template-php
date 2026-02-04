# AI-regler - {{COMPANY_NAME}}

## Prioritetsordning

1. **Brand Guide** (`.windsurf/brand-guide.md`) - Färger, typsnitt, tonalitet
2. **Tekniska regler** (detta dokument) - Kodstandarder, arkitektur
3. **Wireframes** (`relume/`) - Layout och struktur

Vid konflikt gäller högre prioritet.

---

## Referensfiler

- `.windsurf/brand-guide.md` - Varumärkesguide
- `assets/css/variables.css` - CSS-variabler (design tokens) — **ÄNDRA ALDRIG DIREKT**
- `assets/css/overrides.css` - Visuella overrides — **SKRIV ALLTID HÄR**
- `config.php` - Konfiguration (skapa aldrig ny, redigera befintlig)
- `data/content.json` - Innehållsdata

---

## CSS-lager (override-protokoll)

### Arkitektur
```
variables.css    → Foundation (ägs av setup-wizarden)
components.css   → Baskomponenter (använder variabler)
overrides.css    → Manuella ändringar (VINNER ALLTID)
```

### Regler — OBLIGATORISKT
1. **Alla visuella ändringar → `overrides.css`**. Inga undantag.
2. Ändra ALDRIG `variables.css` eller `components.css` direkt
3. `overrides.css` laddas sist och vinner automatiskt via CSS cascade
4. Wizarden kan köras om utan att overrides försvinner

### När du skapar en sektion från en referens/bild
1. **Kopiera STRUKTUREN exakt** — layout, grid, spacing, element-hierarki
2. **Applicera ALLTID brand guiden** — färger, typsnitt, hörnradier, knappstil
3. Använd CSS-variabler: `var(--color-primary)`, `var(--font-heading)`, `var(--radius-md)` etc.
4. Hårdkoda ALDRIG färger eller typsnitt — hämta alltid från `variables.css`
5. Om en specifik komponent behöver avvika från brand guiden, skriv overriden i `overrides.css`

### Exempel: Kopiera en hero-sektion
```css
/* I overrides.css — bara om strukturen kräver något unikt */
.hero-custom {
    /* Layout kopierad från referens */
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 80vh;
}

/* Färger och typsnitt hämtas ALLTID från variabler */
.hero-custom h1 {
    font-family: var(--font-heading);
    color: var(--color-foreground);
}

.hero-custom .cta {
    background: var(--color-primary);
    border-radius: var(--button-radius);
}
```

---

## Kodstandarder

### PHP
- PHP 8.0+ syntax
- Inga ramverk - ren PHP
- `require_once` för includes
- Alla strängar i `htmlspecialchars()` vid output
- Använd `defined('CONSTANT')` för att checka config-värden
- Filstruktur: En ansvarsområde per fil
- Kommentarer på svenska

### CSS / BEM
- Använd CSS custom properties från `variables.css`
- BEM-namngivning: `.block__element--modifier`
- Inga inline-styles (undantag: CMS-genererat innehåll)
- Responsiv design med mobile-first approach
- Breakpoints: `768px` (tablet), `1024px` (desktop)
- Använd `var(--color-primary)` istället för hårdkodade färger
- **Designändringar skrivs ALLTID i `overrides.css`**

### Kontrast & Tillgänglighet — OBLIGATORISKT
Alla färgval MÅSTE följa dessa regler:
1. **Mörk bakgrund → ljus text**: Om bakgrundsfärgen är mörk (t.ex. `--color-gray-800`, `--color-gray-900`, primärfärg med mörk nyans) → använd `white` eller `--color-gray-100` för text
2. **Ljus bakgrund → mörk text**: Om bakgrundsfärgen är ljus (t.ex. `white`, `--color-gray-50`, `--color-gray-100`) → använd `--color-foreground` eller `--color-gray-900` för text
3. **Knappar med bakgrundsfärg**: Text på `--color-primary`-knapp ska ALLTID vara `white`
4. **Subtexts/mutad text**: På ljus bakgrund → `--color-gray-500`/`--color-gray-600`. På mörk bakgrund → `--color-gray-300`/`--color-gray-400`
5. **Länkar**: Säkerställ tillräcklig kontrast mot bakgrunden. På mörk bakgrund: använd `--color-primary-light` eller `white`
6. **Minimalt kontrastförhållande**: Text ska uppfylla WCAG AA (4.5:1 för normal text, 3:1 för stor text)

Snabbguide:
| Bakgrund | Rubrikfärg | Brödtext | Subtexts |
|----------|-----------|----------|----------|
| `white` / `--color-gray-50` | `--color-foreground` | `--color-foreground` | `--color-gray-500` |
| `--color-gray-800` / `--color-gray-900` | `white` | `--color-gray-100` | `--color-gray-400` |
| `--color-primary` | `white` | `white` | `rgba(255,255,255,0.8)` |

### JavaScript
- Vanilla JS - inga ramverk
- `DOMContentLoaded` event listeners
- Prefix custom events med `bosse:`
- Använd `fetch()` för API-anrop
- Felhantering med try/catch

---

## SEO-regler

- Varje sida måste ha unik `<title>` och `<meta name="description">`
- Använd `generateMeta()` från `seo/meta.php`
- Schema.org markup via `seo/schema.php`
- Bilder ska ha `alt`-attribut
- Semantisk HTML: `<header>`, `<main>`, `<section>`, `<footer>`
- Lazy loading på bilder under fold: `loading="lazy"`

---

## Säkerhetsregler

- Alla formulär ska ha CSRF-token via `csrf_field()`
- Lösenord hashas med `password_hash()` (bcrypt)
- Ingen SQL - använd JSON-filer i `data/`
- Validera ALL användarinput server-side
- Sanitize output med `htmlspecialchars()`
- Filer i `uploads/` får inte exekvera PHP
- Session-inställningar: httponly, secure, samesite=strict

---

## Innehållshantering

### Redigerbara texter
Använd `editable_text()` för innehåll som ska vara redigerbart via CMS:

```php
<?php editable_text('section.key', 'Standardtext', 'h2', 'css-klass'); ?>
```

### Innehållsdata
- Sparas i `data/content.json`
- Hämta med `get_content('key', 'default')`
- Strukturera hierarkiskt: `section.element.property`

### Bilder
- Ladda upp via CMS till `uploads/`
- Referera med `/uploads/filnamn.ext`
- Max storlek: 5MB
- Tillåtna format: jpg, png, webp, svg

---

## CMS-användning

### Inlägg/Projekt

Inlägg hanteras via JSON-fil. Skapa INTE inlägg manuellt i filer — använd CMS:et (`/cms/projects/new`) eller följ detta format:

**Filplats:** `data/projects.json`

**Format per inlägg:**
```json
{
  "id": "unikt-id-123",
  "title": "Titel på inlägget",
  "slug": "url-vanlig-titel",
  "category": "Projekt|Blogg|Nyhet|Event",
  "summary": "Kort beskrivning",
  "content": "Fullständig beskrivning av inlägget...",
  "status": "published|draft",
  "coverImage": "/uploads/bild.jpg",
  "gallery": [],
  "createdAt": "2026-02-04 12:00:00"
}
```

**Viktigt:**
- `status` måste vara `"published"` för att synas publikt
- `slug` genereras från titel (å→a, ä→a, ö→o, mellanslag→bindestreck)
- Använd `uniqid()` eller UUID för `id`
- Publika projekt visas på `/projekt` och `/projekt/{slug}`

### Redigerbara fält

När du skapar nya sektioner, använd ALLTID `editable_text()` och `editable_image()`:

```php
<?php editable_text('sektion', 'titel', 'Standardrubrik', 'h2', 'css-klass'); ?>
<?php editable_text('sektion', 'text', 'Standardtext', 'p', 'css-klass'); ?>
<?php editable_image('sektion', 'bild', '/assets/images/placeholder.jpg', 'Alt-text', 'css-klass'); ?>
```

**Parametrar för editable_text():**
1. `$contentKey` — Sektionsnamn (t.ex. 'hero', 'about', 'services')
2. `$field` — Fältnamn (t.ex. 'title', 'description')
3. `$defaultValue` — Standardvärde (visas om inget finns i content.json)
4. `$as` — HTML-tagg (h1, h2, h3, p, span, div)
5. `$className` — CSS-klasser

**Parametrar för editable_image():**
1. `$contentKey` — Sektionsnamn
2. `$field` — Fältnamn
3. `$defaultValue` — Standard bildväg
4. `$alt` — Alt-text
5. `$className` — CSS-klasser

**Redigerbarhet kräver:**
- Användaren måste vara inloggad i CMS (`/admin`)
- Admin-bar måste vara synlig (inkluderas via `includes/admin-bar.php`)
- "Aktivera redigering" måste vara klickad i admin-bar

**Exempel på ny sektion:**
```php
<section class="section section--white">
    <div class="container">
        <?php editable_text('about', 'title', 'Om oss', 'h2', 'text-center'); ?>
        <?php editable_text('about', 'description', 'Vi är ett företag...', 'p', 'text-lg text-center'); ?>
        <?php editable_image('about', 'image', '/assets/images/team.jpg', 'Vårt team', 'about-image'); ?>
    </div>
</section>
```

### Filöversikt — Publikt vs Admin

| Typ | Filer | Beskrivning |
|-----|-------|-------------|
| **Publika sidor** | `index.php`, `kontakt.php`, `projekt.php`, `projekt-single.php`, `cookies.php`, `integritetspolicy.php` | Synliga för besökare |
| **CMS-admin** | `cms/*.php`, `cms/projects/*.php` | Kräver inloggning |
| **Data** | `data/content.json`, `data/projects.json` | JSON-lagring |
| **Uploads** | `uploads/` | Uppladdade bilder (max 5MB per fil) |

---

## Filstruktur

```
/
├── index.php              # Huvudsida
├── kontakt.php            # Kontaktformulär
├── projekt.php            # Publika projekt-lista
├── projekt-single.php     # Enskilt projekt
├── router.php             # URL-routing
├── bootstrap.php          # Miljösetup
├── config.php             # Konfiguration (gitignored)
├── assets/
│   ├── css/
│   │   ├── variables.css  # Design tokens (ÄNDRA EJ)
│   │   ├── components.css # Komponenter (ÄNDRA EJ)
│   │   ├── overrides.css  # Designändringar (SKRIV HÄR)
│   │   └── main.css       # Import-fil
│   ├── js/
│   │   └── cms.js         # CMS JavaScript
│   └── images/            # Statiska bilder
├── cms/                   # Admin-sidor
│   ├── admin.php          # Inloggning
│   ├── dashboard.php      # Översikt
│   └── projects/          # Inlägg-hantering
│       ├── index.php      # Lista inlägg
│       ├── new.php        # Skapa inlägg
│       └── edit.php       # Redigera inlägg
├── includes/              # PHP-komponenter
│   ├── admin-bar.php      # Admin-bar (visas vid inloggning)
│   ├── header.php         # Global header
│   ├── footer.php         # Global footer
│   └── mailer.php         # SMTP-mailsystem
├── data/                  # JSON-data
│   ├── content.json       # Sidinnehåll
│   └── projects.json      # Inlägg/projekt
├── security/              # Säkerhetsmoduler
├── seo/                   # SEO-verktyg
└── uploads/               # Användaruppladdningar
```
