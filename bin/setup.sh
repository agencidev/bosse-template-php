#!/bin/bash
# Setup script - Körs automatiskt vid kloning från GitHub

echo "🚀 Sätter upp projektet..."

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
echo "1. Starta server: php -S localhost:8000 router.php"
echo "2. Gå till http://localhost:8000/setup för att konfigurera sajten"
