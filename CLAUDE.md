# CLAUDE.md

Se `.windsurf/ai-rules.md` för fullständiga AI-regler och kodstandarder.
Se `.windsurf/brand-guide.md` för varumärkesguide (färger, typsnitt, tonalitet).

## Snabbref

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
- `/` — Huvudsida (index.php)
- `/kontakt` — Kontaktformulär
- `/projekt` — Projekt-lista
- `/projekt/{slug}` — Enskilt projekt

### CMS-admin (kräver inloggning)
- `/admin` — Logga in
- `/dashboard` — Översikt
- `/projects` — Hantera inlägg
