# Brand Guide - {{COMPANY_NAME}}

> Genererad: {{DATE}}

---

## Färger

### Primärfärg
- **Hex:** `{{PRIMARY_COLOR}}`
- Används för: Knappar, länkar, viktiga element, CTA:er

### Sekundärfärg
- **Hex:** `{{SECONDARY_COLOR}}`
- Används för: Sekundära knappar, accenter, hover-effekter

### Accentfärg
- **Hex:** `{{ACCENT_COLOR}}`
- Används för: Notifieringar, badges, markeringar

### Neutrala färger
- Bakgrund: `#ffffff`
- Förgrund: `#18181b`
- Grå skala: Zinc-paletten (`#fafafa` till `#18181b`)

---

## Typografi

### Rubriker
- **Typsnitt:** {{FONT_HEADING}}
- Vikter: 600 (semibold), 700 (bold)
- Storlekar: h1 (3rem), h2 (2.25rem), h3 (1.875rem), h4 (1.5rem)

### Brödtext
- **Typsnitt:** {{FONT_BODY}}
- Vikt: 400 (normal), 500 (medium)
- Storlek: 1rem (16px)
- Radhöjd: 1.5

### Monospace
- **Typsnitt:** Courier New, monospace
- Används för: Kodsnuttar, teknisk information

---

## Logotyper

### Mörk bakgrund
- Fil: `assets/images/logo-dark.png`
- Används på: Header, footer med mörk bakgrund

### Ljus bakgrund
- Fil: `assets/images/logo-light.png`
- Används på: Ljusa sektioner, utskrifter

### Regler
- Minsta storlek: 120px bredd
- Frizon: Minst 16px runt logotypen
- Förvräng aldrig proportionerna

---

## Form & Stil

### Hörnradie
- **Stil:** {{BORDER_RADIUS_LABEL}}
- CSS-variabel: `var(--radius-md)` = `{{BORDER_RADIUS_VALUE}}`
- Gäller: Knappar, kort, inputs, modaler

### Knappstil
- **Stil:** {{BUTTON_STYLE_LABEL}}
- CSS-variabel: `var(--button-radius)` = `{{BUTTON_RADIUS_VALUE}}`
- Primärknapp: `{{BUTTON_STYLE_DESC}}`

### Layout-bredd
- **Container:** {{LAYOUT_WIDTH_LABEL}} ({{LAYOUT_WIDTH_VALUE}})
- CSS-variabel: `var(--container-max-width)`

### Bakgrundsmönster
- **Stil:** {{BG_PATTERN_LABEL}}
- {{BG_PATTERN_DESC}}

### Spacing-känsla
- **Stil:** {{SPACING_FEEL_LABEL}}
- Sektions-padding: `var(--section-padding)` = `{{SPACING_FEEL_VALUE}}`
- {{SPACING_FEEL_DESC}}

---

## Tonalitet

- **Språk:** Svenska
- **Ton:** {{TONALITY_LABEL}}
- {{TONALITY_DESC}}
- Tilltala användaren med "du/dig"

---

## UI-komponenter

### Knappar
- Primär: `{{PRIMARY_COLOR}}`, vit text, `border-radius: var(--button-radius)`
- Sekundär: `{{SECONDARY_COLOR}}`, vit text
- Outline: Transparent bakgrund, ram i primärfärg
- Padding: `0.75rem 1.5rem`

### Kort
- Bakgrund: Vit
- Ram: `1px solid #e4e4e7`
- Radie: `var(--radius-lg)`

### Formulär
- Inputfält: Ram `#d4d4d8`, radie `var(--radius-md)`
- Focus: Ram i primärfärg
- Etiketter: `font-weight: 600`, `color: #18181b`

### Spacing
- Sektions-padding: `4rem` (vertikalt)
- Container max-bredd: `{{LAYOUT_WIDTH_VALUE}}`
- Grid gap: `1.5rem`

---

## Override-system

Brand guiden definierar **fundamentet**. Specifika avvikelser skrivs i `assets/css/overrides.css`.

```
variables.css    → Denna brand guide (genererad av wizard)
components.css   → Baskomponenter
overrides.css    → Avvikelser och unika designer
```

Exempel: Om en specifik knapp ska ha en annan stil än brand guiden:
```css
/* assets/css/overrides.css */
.hero .cta-special {
    background: linear-gradient(135deg, #ff6b35, #fe4f2a);
    border-radius: 0;
}
```

---

## CMS-integration

För fullständig dokumentation av CMS-systemet, se `.rules/ai-rules.md` under sektionen **"CMS-användning"**.

### Snabbref

- **Sidinnehåll:** `data/content.json`
- **Inlägg/projekt:** `data/projects.json`
- **Redigerbara element:** Använd `editable_text()` och `editable_image()`
- **Publika projekt:** Visas på `/projekt` och `/projekt/{slug}`
