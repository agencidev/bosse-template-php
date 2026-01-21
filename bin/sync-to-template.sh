#!/bin/bash
# Synka ändringar från test-projekt till bosse-template-php

SOURCE_DIR="/Users/christianhagler/Desktop/Utveckling (WINDSURF)/bolaget/"
TARGET_DIR="/Users/christianhagler/Desktop/Utveckling (WINDSURF)/bosse/apps/template/php/"

echo "🔄 Synkar ändringar till bosse-template-php..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Källa: $SOURCE_DIR"
echo "Mål: $TARGET_DIR"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Kontrollera att målmappen finns
if [ ! -d "$TARGET_DIR" ]; then
    echo "❌ Målmappen finns inte: $TARGET_DIR"
    exit 1
fi

# Rsync med exkluderingar
rsync -av --delete \
    --exclude='.git' \
    --exclude='data/content.json' \
    --exclude='public/uploads/*' \
    --exclude='.DS_Store' \
    --exclude='node_modules' \
    --exclude='.env' \
    "$SOURCE_DIR" "$TARGET_DIR"

echo ""
echo "✅ Synkning klar!"
echo ""
echo "Nästa steg:"
echo "1. Testa på localhost:8001"
echo "2. Pusha till GitHub (agencidev/bosse-template-php)"
