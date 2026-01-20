# PHP Website Template

Modern PHP-template med inbyggd säkerhet, SEO och CMS.

## Setup

### 1. Klona projektet
```bash
git clone https://github.com/mackew/ditt-projekt.git
cd ditt-projekt
```

### 2. Konfigurera environment
```bash
cp .env.example .env
cp config.example.php config.php
```

Uppdatera `.env` med dina värden:
```env
ADMIN_USERNAME=admin
ADMIN_PASSWORD=ditt-säkra-lösenord
SESSION_SECRET=generera-random-secret
SITE_URL=https://din-domain.se
SITE_NAME=Ditt Företag
```

### 3. Skapa nödvändiga mappar
```bash
mkdir -p uploads data
touch data/content.json
echo '{}' > data/content.json
```

### 4. Sätt rättigheter
```bash
chmod 755 uploads
chmod 755 data
chmod 644 data/content.json
```

### 5. Starta lokal server
```bash
php -S localhost:8000
```

Öppna: http://localhost:8000

## Struktur

```
project-root/
├── assets/
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript
│   └── images/           # Bilder
├── cms/
│   ├── admin.php         # CMS admin-panel
│   ├── auth.php          # Autentisering
│   └── api.php           # API-endpoints
├── security/
│   ├── csrf.php          # CSRF-skydd
│   ├── validation.php    # Input-validering
│   └── session.php       # Session-hantering
├── seo/
│   ├── meta.php          # Meta-tags
│   ├── schema.php        # Structured data
│   ├── sitemap.php       # Sitemap generator
│   └── robots.txt        # Robots.txt
├── includes/
│   ├── header.php        # Header-komponent
│   └── footer.php        # Footer-komponent
├── data/
│   └── content.json      # CMS-innehåll
├── relume/
│   ├── wireframes/       # Relume wireframes
│   ├── sitemap.json      # Relume sitemap
│   └── style-guide.json  # Relume style guide
└── index.php             # Huvudsida
```

## CMS - WordPress-liknande Inline-redigering

### Logga in
- URL: `/cms/admin.php`
- Username: Se `.env` (ADMIN_USERNAME)
- Password: Se `.env` (ADMIN_PASSWORD)

### Funktioner
- ✅ **Admin Bar** - Toolbar högst upp när du är inloggad
- ✅ **Inline-redigering** - Klicka på text/bilder för att redigera direkt på sidan
- ✅ **Hover-effekter** - Gul highlight när du hovrar över redigerbara element
- ✅ **Spara/Avbryt** - Knappar för att spara eller avbryta ändringar
- ✅ **Keyboard shortcuts** - Enter för att spara, Escape för att avbryta
- ✅ **Visuell feedback** - Tooltips och notifikationer

### Användning

**Redigera text:**
```php
<?php editable_text('hero.title', 'Standard rubrik', 'h1'); ?>
<?php editable_text('hero.description', 'Standard beskrivning', 'p', 'text-lg'); ?>
```

**Redigera bilder:**
```php
<?php editable_image('hero.image', '/assets/images/default.jpg', 'Hero bild', 'w-full'); ?>
```

När du är inloggad:
1. Hovra över text/bilder → Ser gul highlight
2. Klicka för att redigera → Input-fält visas
3. Gör ändringar → Klicka "Spara" eller tryck Enter
4. Ändringar sparas automatiskt till `data/content.json`

## SEO

### Meta-tags
Varje sida använder `seo/meta.php` för att generera SEO meta-tags:

```php
<?php
require_once 'seo/meta.php';
generateMeta(
  'Sidtitel',
  'Sidbeskrivning 150-160 tecken',
  '/assets/images/og-image.jpg'
);
?>
```

### Structured Data
Lägg till structured data med `seo/schema.php`:

```php
<?php
require_once 'seo/schema.php';
echo organizationSchema();
echo websiteSchema();
?>
```

### Sitemap
Sitemap genereras automatiskt på `/seo/sitemap.php`

## Säkerhet

### CSRF-skydd
Alla formulär måste ha CSRF-token:

```php
<?php require_once 'security/csrf.php'; ?>
<form method="POST">
  <?php echo csrf_field(); ?>
  <input type="text" name="name">
  <button type="submit">Skicka</button>
</form>
```

Validera på server-sidan:
```php
<?php
require_once 'security/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  // Process form
}
?>
```

### Input-validering
```php
<?php
require_once 'security/validation.php';

$name = $_POST['name'] ?? '';
if (!validate_text($name, 2, 100)) {
  die('Ogiltigt namn');
}

$name = sanitize_text($name);
?>
```

## Relume Integration

### Workflow
1. Designa i Relume
2. Exportera wireframes, sitemap, style guide
3. Lägg filer i `relume/`-foldern
4. Konvertera style guide → CSS variables i `assets/css/variables.css`
5. Implementera wireframes som HTML/PHP

## Deployment

### Checklist
- [ ] Uppdatera `.env` med production-värden
- [ ] Aktivera HTTPS redirect i `.htaccess`
- [ ] Sätt `ENVIRONMENT=production` i `.env`
- [ ] Testa alla formulär (CSRF, validering)
- [ ] Verifiera SEO meta-tags
- [ ] Kör Lighthouse test (mål: >90)
- [ ] Testa cross-browser (Chrome, Firefox, Safari)
- [ ] Sätt upp backup-rutin

### Hosting
1. Ladda upp alla filer till server
2. Sätt rättigheter på `uploads/` och `data/`
3. Konfigurera `.env`
4. Verifiera att `.htaccess` fungerar
5. Testa site

## Support

För frågor eller problem, kontakta utvecklingsteamet.

## License

Proprietary - Alla rättigheter förbehållna
