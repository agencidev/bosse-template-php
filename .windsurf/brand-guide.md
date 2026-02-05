# Brand Guide - Målarkompetens AB

> Genererad: 2026-02-02

---

## Färger

### Primärfärg
- **Hex:** `#8b5cf6`
- Används för: Knappar, länkar, viktiga element, CTA:er

### Sekundärfärg
- **Hex:** `#ff6b35`
- Används för: Sekundära knappar, accenter, hover-effekter

### Accentfärg
- **Hex:** `#fe4f2a`
- Används för: Notifieringar, badges, markeringar

### Neutrala färger
- Bakgrund: `#ffffff`
- Förgrund: `#18181b`
- Grå skala: Zinc-paletten (`#fafafa` till `#18181b`)

---

## Typografi

### Rubriker
- **Typsnitt:** Poppins
- Vikter: 600 (semibold), 700 (bold)
- Storlekar: h1 (3rem), h2 (2.25rem), h3 (1.875rem), h4 (1.5rem)

### Brödtext
- **Typsnitt:** Montserrat
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
- **Stil:** Rundad (8px)
- CSS-variabel: `var(--radius-md)` = `0.5rem`
- Gäller: Knappar, kort, inputs, modaler

### Knappstil
- **Stil:** Fylld
- CSS-variabel: `var(--button-radius)` = `0.5rem`
- Primärknapp: `Bakgrundsfärg med vit text`

### Layout-bredd
- **Container:** Normal (1200px)
- CSS-variabel: `var(--container-max-width)`

### Bakgrundsmönster
- **Stil:** Solid (enfärgad)
- Rena, enfärgade bakgrunder. Sektioner alternerar mellan vit och grå.

### Spacing-känsla
- **Stil:** Normal
- Sektions-padding: `var(--section-padding)` = `4rem`
- Standard sektions-padding (4rem). Balanserad känsla.

---

## Tonalitet

- **Språk:** Svenska
- **Ton:** Minimalistisk
- - **Stil:** Kort, ren, avskalad
- Undvik: Överflödiga ord, utsmyckningar, långa meningar
- Tilltala användaren med "du/dig"

---

## UI-komponenter

### Knappar
- Primär: `#8b5cf6`, vit text, `border-radius: var(--button-radius)`
- Sekundär: `#ff6b35`, vit text
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
- Container max-bredd: `1200px`
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
