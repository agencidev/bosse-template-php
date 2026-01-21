#!/bin/bash
# Setup script - Körs automatiskt vid kloning från GitHub

echo "🚀 Sätter upp projektet..."

# Kopiera .env.example till .env om den inte finns
if [ ! -f .env ]; then
    echo "📝 Skapar .env från .env.example..."
    cp .env.example .env
    echo "✅ .env skapad!"
else
    echo "✅ .env finns redan"
fi

# Skapa nödvändiga mappar
echo "📁 Skapar mappar..."
mkdir -p data
mkdir -p uploads
mkdir -p public/uploads

# Sätt rättigheter
echo "🔒 Sätter rättigheter..."
chmod 755 data uploads public/uploads 2>/dev/null || true

echo "✅ Setup klar!"
echo ""
echo "Nästa steg:"
echo "1. Redigera .env och sätt dina värden"
echo "2. Starta server: php -S localhost:8000 router.php"
echo "3. Gå till http://localhost:8000/admin för att logga in"
