# Relume Workflow - Från Wireframe till Färdig Sida

## 🎯 Process: Relume → Bosse Template

### Steg 1: Exportera från Relume

1. **Öppna ditt Relume-projekt**
2. **Välj wireframe/komponent** du vill använda
3. **Exportera HTML/CSS:**
   - Klicka "Export" → "HTML + Tailwind CSS"
   - Ladda ner filen
4. **Spara i projektet:**
   ```bash
   # Placera wireframe-filen här:
   relume/wireframes/hero-section.html
   relume/wireframes/features-section.html
   relume/wireframes/cta-section.html
   ```

---

### Steg 2: Konvertera till PHP + CMS

**Från Relume HTML:**
```html
<section class="py-16 md:py-24 lg:py-28">
  <div class="container">
    <h1 class="text-5xl font-bold">Welcome to Our Site</h1>
    <p class="text-lg">We help you achieve your goals</p>
  </div>
</section>
```

**Till PHP med CMS:**
```php
<section class="py-16 md:py-24 lg:py-28">
  <div class="container">
    <?php editable_text('hero', 'title', 'Welcome to Our Site', 'h1', 'text-5xl font-bold'); ?>
    <?php editable_text('hero', 'description', 'We help you achieve your goals', 'p', 'text-lg'); ?>
  </div>
</section>
```

---

### Steg 3: Implementera i index.php

**Öppna:** `index.php`

**Ersätt befintlig sektion:**
```php
<!-- Hero Section -->
<section class="section section--white">
    <div class="container text-center">
        <?php editable_text('hero', 'title', 'Välkommen till vår hemsida', 'h1'); ?>
        <?php editable_text('hero', 'description', 'Vi hjälper dig att nå dina mål', 'p', 'text-lg'); ?>
    </div>
</section>
```

**Med Relume-koden:**
```php
<!-- Hero Section - Relume Layout 1 -->
<section class="py-16 md:py-24 lg:py-28">
    <div class="container max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <?php editable_text('hero', 'title', 'Welcome to Our Site', 'h1', 'text-5xl font-bold mb-6'); ?>
                <?php editable_text('hero', 'description', 'We help you achieve your goals', 'p', 'text-lg mb-8'); ?>
                <a href="/kontakt" class="button button--primary">
                    <?php echo get_content('hero.cta_text', 'Get Started'); ?>
                </a>
            </div>
            <div>
                <?php editable_image('hero', 'image', '/assets/images/hero.jpg', 'Hero image', 'rounded-lg shadow-xl'); ?>
            </div>
        </div>
    </div>
</section>
```

---

### Steg 4: Anpassa CSS (om nödvändigt)

**Om Relume använder Tailwind CSS:**
- Koden fungerar direkt (Tailwind finns redan i templaten)

**Om Relume använder custom CSS:**
1. Kopiera CSS från Relume
2. Lägg till i `assets/css/components.css`
3. Anpassa färger till template-färger:
   ```css
   /* Relume färger → Template färger */
   #000000 → var(--color-woodsmoke)  /* Svart */
   #FF5722 → var(--color-persimmon)  /* Orange */
   ```

---

### Steg 5: Testa och Justera

1. **Öppna sidan:** `http://localhost:8000`
2. **Logga in:** `/admin` (admin/admin123)
3. **Aktivera redigering** i AdminBar
4. **Klicka på text/bilder** för att testa inline-redigering
5. **Justera spacing/layout** om nödvändigt

---

## 📋 Checklista för Varje Sida

När du skapar en ny sida från Relume:

### ✅ Design & Layout
- [ ] Exportera wireframe från Relume
- [ ] Spara i `relume/wireframes/`
- [ ] Konvertera HTML till PHP
- [ ] Lägg till `editable_text()` för all text
- [ ] Lägg till `editable_image()` för alla bilder

### ✅ CMS Integration
- [ ] Alla rubriker är redigerbara
- [ ] All brödtext är redigerbar
- [ ] Alla bilder är redigerbara
- [ ] CTA-knappar har redigerbar text
- [ ] Använd unika `contentKey` för varje sektion

### ✅ SEO
- [ ] Meta-title satt (via `generateMeta()`)
- [ ] Meta-description satt
- [ ] OG-image satt
- [ ] Schema.org markup (via `organizationSchema()`, `websiteSchema()`)
- [ ] Alt-text på alla bilder

### ✅ Säkerhet
- [ ] Inga hårdkodade känsliga data
- [ ] All user input valideras (om formulär finns)
- [ ] CSRF-token på formulär
- [ ] XSS-skydd (via `htmlspecialchars()`)

### ✅ Prestanda
- [ ] Bilder optimerade (WebP om möjligt)
- [ ] Lazy loading på bilder
- [ ] CSS minifierad i produktion
- [ ] JavaScript minifierad i produktion

### ✅ Responsivitet
- [ ] Testad på mobil (< 768px)
- [ ] Testad på tablet (768px - 1024px)
- [ ] Testad på desktop (> 1024px)
- [ ] Breakpoints från Relume behållna

### ✅ Tillgänglighet
- [ ] Semantisk HTML (h1, h2, section, nav, etc.)
- [ ] ARIA-labels där nödvändigt
- [ ] Keyboard navigation fungerar
- [ ] Kontrast-ratio minst 4.5:1

---

## 🎨 Relume → Template Mapping

### Färger
```css
/* Relume Default → Template */
Primary Color   → var(--color-persimmon)  /* #FF5722 */
Dark Color      → var(--color-woodsmoke)  /* #18181B */
Light Color     → var(--color-white)      /* #FFFFFF */
Gray Color      → var(--color-neutral-*)  /* #737373, etc */
```

### Typography
```css
/* Relume → Template */
font-family: Inter → System fonts (-apple-system, BlinkMacSystemFont)
```

### Spacing
```css
/* Relume Tailwind → Template */
py-16 → section padding (behåll Tailwind-klasser)
container → max-w-7xl mx-auto px-6
```

---

## 💡 Pro-tips

### 1. **Återanvänd Sektioner**
Skapa komponenter i `includes/` för återanvändning:
```php
// includes/hero-relume-1.php
<section class="py-16 md:py-24">
    <div class="container">
        <?php editable_text($contentKey, 'title', $defaultTitle, 'h1', 'text-5xl font-bold'); ?>
    </div>
</section>

// Använd i index.php:
<?php include __DIR__ . '/includes/hero-relume-1.php'; ?>
```

### 2. **Dokumentera Wireframes**
Skapa en `relume/wireframes/INDEX.md`:
```markdown
# Wireframes

- hero-1.html - Hero section med bild till höger
- features-3col.html - Features med 3 kolumner
- cta-centered.html - Centrerad CTA med bakgrundsbild
```

### 3. **Style Guide**
Spara Relume style guide i `relume/style-guide.json`:
```json
{
  "colors": {
    "primary": "#FF5722",
    "dark": "#18181B"
  },
  "typography": {
    "h1": "text-5xl font-bold",
    "h2": "text-4xl font-bold",
    "body": "text-base"
  }
}
```

---

## 🚀 Snabbstart: Ny Sida från Relume

```bash
# 1. Exportera från Relume
# 2. Spara wireframe
cp ~/Downloads/relume-export.html relume/wireframes/new-section.html

# 3. Öppna index.php
# 4. Kopiera HTML från wireframe
# 5. Ersätt text med editable_text()
# 6. Ersätt bilder med editable_image()
# 7. Testa på localhost:8000
# 8. Logga in och testa redigering
# 9. Commit och push!
```

---

## 📚 Exempel: Komplett Relume-sektion

**Relume Export:**
```html
<section class="px-[5%] py-16 md:py-24 lg:py-28">
  <div class="container">
    <div class="mx-auto mb-12 w-full max-w-lg text-center md:mb-18 lg:mb-20">
      <h2 class="mb-5 text-5xl font-bold md:mb-6 md:text-7xl lg:text-8xl">
        Medium length section heading goes here
      </h2>
      <p class="md:text-md">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit.
      </p>
    </div>
  </div>
</section>
```

**Konverterad till PHP + CMS:**
```php
<section class="px-[5%] py-16 md:py-24 lg:py-28">
  <div class="container">
    <div class="mx-auto mb-12 w-full max-w-lg text-center md:mb-18 lg:mb-20">
      <?php editable_text(
        'features',
        'title',
        'Medium length section heading goes here',
        'h2',
        'mb-5 text-5xl font-bold md:mb-6 md:text-7xl lg:text-8xl'
      ); ?>
      <?php editable_text(
        'features',
        'description',
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        'p',
        'md:text-md'
      ); ?>
    </div>
  </div>
</section>
```

---

**Klart! Nu har du en komplett guide för Relume → Bosse Template workflow!** 🦸‍♂️
