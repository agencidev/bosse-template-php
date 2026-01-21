---
description: Onboarding-checklista för nya projekt från bosse-template-php
---

# 🚀 Onboarding-checklista för nya projekt

Följ denna checklista när du klonat ner bosse-template-php till ett nytt projekt.

## 1. Grundkonfiguration

### Företagsinformation
- [ ] Uppdatera `SITE_NAME` i `config.example.php`
- [ ] Uppdatera `CONTACT_EMAIL` i `config.example.php`
- [ ] Uppdatera `ADMIN_USERNAME` och `ADMIN_PASSWORD`

### Säkerhet
- [ ] Generera ny `CSRF_SECRET` (använd `bin/generate-secret.php`)
- [ ] Kontrollera att `.env` är i `.gitignore`
- [ ] Verifiera att känsliga filer är skyddade i `.htaccess`

## 2. Design och innehåll

### Logotyper
- [ ] Ersätt `/assets/images/logo-light.png` (ljus logotyp för mörk bakgrund)
- [ ] Ersätt `/assets/images/logo-dark.png` (mörk logotyp för ljus bakgrund)
- [ ] Kontrollera att logotyperna ser bra ut i admin bar

### Färger
- [ ] Uppdatera CTA-färg i `/assets/css/variables.css` (standard: #fe4f2a)
- [ ] Kontrollera att alla knappar använder rätt färg
- [ ] Verifiera att färgerna matchar kundens grafiska profil

### Innehåll
- [ ] Uppdatera hemsidans innehåll i `data/content.json`
- [ ] Lägg till företagets kontaktinformation
- [ ] Anpassa SEO-metadata (title, description)

## 3. Funktionalitet

### Cookie Consent
- [ ] Verifiera att företagsnamn och email visas korrekt i cookie-bannern
- [ ] Testa att cookie-inställningar sparas
- [ ] Kontrollera Google Consent Mode integration

### CMS
- [ ] Logga in på `/admin` med nya credentials
- [ ] Testa inline-redigering på hemsidan
- [ ] Skapa ett testinlägg
- [ ] Verifiera att bilder kan laddas upp

### Navigation
- [ ] Testa alla länkar i menyn
- [ ] Kontrollera att admin bar fungerar korrekt
- [ ] Verifiera logout-funktionalitet

## 4. Teknisk setup

### Server
- [ ] Starta utvecklingsserver: `php -S localhost:8000 router.php`
- [ ] Verifiera att alla sidor laddar korrekt
- [ ] Testa URL-routing utan .php-extension

### Databas/Innehåll
- [ ] Kontrollera att `data/` mappen finns
- [ ] Verifiera att `data/content.json` har rätt struktur
- [ ] Testa att innehållsändringar sparas

### Uploads
- [ ] Kontrollera att `public/uploads/` finns och är skrivbar
- [ ] Testa bilduppladdning
- [ ] Verifiera att PHP inte kan köras i uploads-mappen

## 5. Produktion

### Optimering
- [ ] Minifiera CSS och JavaScript
- [ ] Optimera bilder
- [ ] Aktivera gzip-komprimering i `.htaccess`

### Säkerhet
- [ ] Aktivera HTTPS-redirect i `.htaccess`
- [ ] Uppdatera Content Security Policy
- [ ] Kontrollera säkerhetsheaders

### Analytics
- [ ] Lägg till Google Analytics tracking ID (om tillämpligt)
- [ ] Verifiera att Google Consent Mode fungerar
- [ ] Testa att analytics-data samlas in korrekt

## 6. Deployment

### Git
- [ ] Initiera nytt Git-repo (om inte redan gjort)
- [ ] Lägg till `.gitignore` med rätt exkluderingar
- [ ] Gör första commit med alla filer

### Hosting
- [ ] Konfigurera produktionsserver
- [ ] Sätt upp databas/fillagring
- [ ] Konfigurera domän och SSL-certifikat

### Backup
- [ ] Sätt upp automatiska backups av innehåll
- [ ] Dokumentera återställningsprocess
- [ ] Testa backup-återställning

## 7. Dokumentation

### Kund
- [ ] Skapa användarmanual för CMS
- [ ] Dokumentera hur man redigerar innehåll
- [ ] Förklara cookie-inställningar

### Team
- [ ] Dokumentera projektspecifika anpassningar
- [ ] Uppdatera README.md med projektinformation
- [ ] Lägg till kontaktinformation för support

## ✅ Slutkontroll

Kör automatiska tester:
```bash
php bin/test-setup.php
```

Om alla tester är gröna är projektet redo att användas! 🎉

---

**Tips:** Spara denna checklista och bocka av varje punkt när du är klar. Det tar ca 30-45 minuter att slutföra hela onboardingen.
