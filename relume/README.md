# Relume Wireframes

## Workflow

### 1. Designa i Relume
- Skapa sitemap
- Bygg wireframes
- Konfigurera style guide (färger, typografi)

### 2. Exportera från Relume
- **Sitemap:** Exportera → `sitemap.json`
- **Style Guide:** Exportera → `style-guide.json`
- **Wireframes:** Exportera komponenter → `wireframes/`

### 3. Lägg in i projektet
Kopiera exporterade filer till denna mapp:
```
relume/
├── wireframes/
│   ├── header.html
│   ├── hero.html
│   ├── features.html
│   └── footer.html
├── sitemap.json
└── style-guide.json
```

### 4. Konvertera Style Guide → CSS
Använd Relume style guide för att uppdatera CSS-variabler:

**Från `style-guide.json`:**
```json
{
  "colors": {
    "primary": "#6366f1",
    "secondary": "#ec4899"
  },
  "typography": {
    "fontFamily": "Inter",
    "fontSize": {
      "h1": "48px",
      "body": "16px"
    }
  }
}
```

**Till `assets/css/variables.css`:**
```css
:root {
  --color-primary: #6366f1;
  --color-secondary: #ec4899;
  --font-primary: 'Inter', sans-serif;
  --text-5xl: 3rem; /* 48px */
  --text-base: 1rem; /* 16px */
}
```

### 5. Implementera Wireframes
Konvertera Relume wireframes till HTML/PHP-komponenter:

**Från `wireframes/hero.html`:**
```html
<section class="hero">
  <h1>Hero Title</h1>
  <p>Hero description</p>
  <button>CTA Button</button>
</section>
```

**Till PHP-komponent med CMS:**
```php
<section class="section section--white">
  <div class="container text-center">
    <h1><?php echo get_content('hero.title', 'Hero Title'); ?></h1>
    <p><?php echo get_content('hero.description', 'Hero description'); ?></p>
    <a href="<?php echo get_content('hero.cta_link', '/contact'); ?>" class="button button--primary">
      <?php echo get_content('hero.cta_text', 'CTA Button'); ?>
    </a>
  </div>
</section>
```

### 6. Applicera Styling
Använd CSS-variabler från design system:

```css
.hero {
  padding: var(--spacing-16) 0;
  background-color: var(--color-background);
}

.hero h1 {
  color: var(--color-foreground);
  font-size: var(--text-5xl);
  margin-bottom: var(--spacing-4);
}

.hero p {
  color: var(--color-gray-600);
  font-size: var(--text-lg);
  margin-bottom: var(--spacing-6);
}
```

## Tips

- **Konsistens:** Följ design system-reglerna i `/.windsurf/design-system-rules.md`
- **BEM-namngivning:** Använd BEM för CSS-klasser
- **Återanvänd:** Skapa återanvändbara komponenter
- **CMS:** Gör allt innehåll redigerbart via CMS

## Exempel: Komplett Hero-sektion

```php
<?php require_once 'cms/content.php'; ?>

<section class="hero">
  <div class="container">
    <div class="hero__content">
      <h1 class="hero__title">
        <?php echo get_content('hero.title', 'Välkommen till vår hemsida'); ?>
      </h1>
      <p class="hero__description">
        <?php echo get_content('hero.description', 'Vi hjälper dig att nå dina mål'); ?>
      </p>
      <div class="hero__actions">
        <a href="<?php echo get_content('hero.primary_link', '/contact'); ?>" 
           class="button button--primary">
          <?php echo get_content('hero.primary_text', 'Kontakta oss'); ?>
        </a>
        <a href="<?php echo get_content('hero.secondary_link', '/about'); ?>" 
           class="button button--outline">
          <?php echo get_content('hero.secondary_text', 'Läs mer'); ?>
        </a>
      </div>
    </div>
  </div>
</section>
```

```css
.hero {
  padding: var(--spacing-20) 0;
  background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary) 100%);
  color: white;
}

.hero__content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero__title {
  font-size: var(--text-5xl);
  font-weight: var(--font-bold);
  margin-bottom: var(--spacing-4);
}

.hero__description {
  font-size: var(--text-xl);
  margin-bottom: var(--spacing-8);
  opacity: 0.9;
}

.hero__actions {
  display: flex;
  gap: var(--spacing-4);
  justify-content: center;
}

@media (max-width: 768px) {
  .hero__title {
    font-size: var(--text-3xl);
  }
  
  .hero__actions {
    flex-direction: column;
  }
}
```
