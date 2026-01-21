---
description: Konvertera Relume wireframes till PHP med CMS
---

# Relume → PHP Konvertering

Jag har lagt Relume wireframe-filer i `relume/wireframes/`.

**Konvertera alla Relume HTML-filer till PHP med CMS-funktionalitet:**

1. **Läs alla filer** i `relume/wireframes/`
2. **För varje HTML-fil:**
   - Identifiera alla text-element (h1, h2, h3, p, span, etc.)
   - Ersätt med `<?php editable_text('contentKey', 'field', 'defaultValue', 'element', 'className'); ?>`
   - Identifiera alla bilder (img, picture)
   - Ersätt med `<?php editable_image('contentKey', 'field', 'defaultSrc', 'altText', 'className'); ?>`
   - Identifiera alla länkar med text (a href)
   - Ersätt text med `<?php echo get_content('contentKey.field', 'defaultValue'); ?>`
   - Behåll ALLA Tailwind CSS-klasser exakt som de är
   - Behåll ALLA HTML-strukturer (div, section, etc.)

3. **Generera contentKey:**
   - Använd sektionsnamn (hero, features, cta, testimonials, etc.)
   - Gör unika per sektion
   - Använd beskrivande field-namn (title, description, image, cta_text, etc.)

4. **Ersätt befintliga sektioner i index.php:**
   - Ta bort gamla placeholder-sektioner
   - Lägg till konverterade Relume-sektioner
   - Behåll header, footer, AdminBar, cookie-consent

5. **Verifiera:**
   - All text är redigerbar via CMS
   - Alla bilder är redigerbara via CMS
   - Alla Tailwind-klasser är intakta
   - HTML-strukturen är giltig
   - Responsivitet fungerar (mobile, tablet, desktop)

**Exempel på konvertering:**

**Från Relume:**
```html
<section class="px-[5%] py-16 md:py-24">
  <div class="container">
    <h1 class="text-6xl font-bold mb-6">
      Welcome to our website
    </h1>
    <p class="text-lg text-gray-600">
      We help you achieve your goals
    </p>
    <img src="placeholder.jpg" alt="Hero" class="w-full rounded-lg" />
  </div>
</section>
```

**Till PHP:**
```php
<section class="px-[5%] py-16 md:py-24">
  <div class="container">
    <?php editable_text('hero', 'title', 'Welcome to our website', 'h1', 'text-6xl font-bold mb-6'); ?>
    <?php editable_text('hero', 'description', 'We help you achieve your goals', 'p', 'text-lg text-gray-600'); ?>
    <?php editable_image('hero', 'image', 'placeholder.jpg', 'Hero', 'w-full rounded-lg'); ?>
  </div>
</section>
```

**Viktigt:**
- Behåll ALLA CSS-klasser från Relume
- Använd unika contentKey för varje sektion
- Sätt beskrivande defaultValues
- Testa att inline-redigering fungerar efter konvertering
