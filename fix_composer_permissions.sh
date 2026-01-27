#!/bin/bash

# Fix composer.json permissions and install pdfparser

echo "🔧 Fixing composer.json permissions..."

# Option 1: Change permissions
chmod 644 composer.json
echo "✅ Permissions changed to 644"

# Option 2: Change ownership (if needed)
# sudo chown $USER:$USER composer.json

# Install pdfparser
echo "📦 Installing smalot/pdfparser..."
composer require smalot/pdfparser

echo "✅ Installation complete!"
composer show | grep pdfparser
