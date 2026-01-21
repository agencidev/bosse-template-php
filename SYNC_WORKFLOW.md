# Workflow: Test-projekt → bosse-template-php

## Steg 1: Arbeta i test-projekt

1. Klona från GitHub-template:
```bash
# Via Bosse Portal eller manuellt:
git clone https://github.com/agencidev/bosse-template-php.git mitt-test-projekt
cd mitt-test-projekt
```

2. Starta lokal server:
```bash
php -S localhost:8000
```

3. Arbeta och testa ändringar live

## Steg 2: Synka tillbaka till bosse-template-php

### Metod A: Manuell kopiering (Rekommenderad för enskilda filer)

```bash
# Kopiera specifika filer från test-projekt till template
cp mitt-test-projekt/cms/admin.php /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php/cms/
cp mitt-test-projekt/includes/admin-bar.php /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php/includes/
# osv...
```

### Metod B: Git diff och patch (Rekommenderad för många ändringar)

```bash
# I test-projektet, skapa en patch-fil:
cd mitt-test-projekt
git diff > ../mina-andringar.patch

# I bosse-template-php, applicera patchen:
cd /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php
git apply ../mina-andringar.patch
```

### Metod C: Rsync (Rekommenderad för hela mappar)

```bash
# Synka hela CMS-mappen:
rsync -av --delete \
  mitt-test-projekt/cms/ \
  /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php/cms/

# Synka assets:
rsync -av --delete \
  mitt-test-projekt/assets/ \
  /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php/assets/
```

## Steg 3: Testa i bosse-template-php

```bash
cd /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php
php -S localhost:8001
# Testa på http://localhost:8001
```

## Steg 4: Pusha till GitHub

```bash
cd /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php
git add -A
git commit -m "Uppdatering från test-projekt: [beskriv ändringar]"
git push origin main
```

## Viktiga filer att synka:

### CMS-kärnfiler:
- `cms/admin.php` (login-sida)
- `cms/dashboard.php` (dashboard)
- `cms/api.php` (API-endpoints)
- `cms/content.php` (editable_text, editable_image)

### Includes:
- `includes/admin-bar.php` (AdminBar)

### Assets:
- `assets/css/cms.css` (CMS-stilar)
- `assets/js/cms.js` (CMS-JavaScript)
- `assets/images/logo-*.png` (Logotyper)

### Security:
- `security/session.php`
- `security/csrf.php`
- `security/validation.php`

## Tips:

1. **Använd git branches i test-projektet:**
   ```bash
   git checkout -b feature/ny-funktion
   # Arbeta...
   git commit -am "Ny funktion klar"
   ```

2. **Jämför filer innan sync:**
   ```bash
   diff mitt-test-projekt/cms/admin.php bosse/apps/template/php/cms/admin.php
   ```

3. **Backup innan stora ändringar:**
   ```bash
   cp -r bosse/apps/template/php bosse/apps/template/php.backup
   ```

4. **Testa alltid efter sync:**
   - Login fungerar
   - Dashboard visas
   - Inline-redigering fungerar
   - Bilduppladdning fungerar
