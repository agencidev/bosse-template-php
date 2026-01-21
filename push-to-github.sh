#!/bin/bash

# Push CMS changes to GitHub
cd /Users/christianhagler/Desktop/Utveckling\ \(WINDSURF\)/bosse/apps/template/php

echo "Adding all changes..."
git add -A

echo "Committing changes..."
git commit -m "CMS EXAKT som Next.js-versionen - Komplett

- Login-sida med agenci-logotyp (EXAKT som LoginForm.jsx)
- AdminBar med agenci-logotyp och aktivera/avsluta redigering
- Dashboard med alla 6 CTA-knappar (Skapa inlägg, Inlägg, Redigera hemsidan, Support, SEO, AI)
- Inline-redigering med samma UX som EditableText.jsx
- CSS med Tailwind utility classes och rätt färger (woodsmoke, persimmon)
- API-endpoints som matchar Next.js
- Alla filer uppdaterade för att matcha Bosse Portal identiskt"

echo "Pushing to GitHub..."
git push origin main

echo "✅ Done! CMS pushed to agencidev/bosse-template-php"
