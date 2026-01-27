#!/bin/bash

echo "🚀 Installing Python dependencies for document extraction..."
echo ""

# Update pip
echo "📦 Updating pip..."
pip3 install --upgrade pip setuptools wheel

# Install required packages
echo "📥 Installing document extraction packages..."
pip3 install pdfplumber==0.10.0 || pip install pdfplumber==0.10.0
pip3 install PyPDF2==4.0.0 || pip install PyPDF2==4.0.0
pip3 install python-docx==0.8.11 || pip install python-docx==0.8.11
pip3 install openpyxl==3.10.0 || pip install openpyxl==3.10.0
pip3 install python-pptx==0.6.23 || pip install python-pptx==0.6.23
pip3 install mysql-connector-python==8.0.33 || pip install mysql-connector-python

# Optional packages for better quality
echo "📥 Installing optional text processing packages..."
pip3 install nltk==3.8.0 || pip install nltk==3.8.0
pip3 install textacy==0.12.0 || pip install textacy==0.12.0

echo ""
echo "✅ Installation complete!"
echo ""
echo "Verifying installations..."
python3 -c "import pdfplumber; print('✓ pdfplumber installed')" 2>/dev/null || echo "✗ pdfplumber failed"
python3 -c "import PyPDF2; print('✓ PyPDF2 installed')" 2>/dev/null || echo "✗ PyPDF2 failed"
python3 -c "import docx; print('✓ python-docx installed')" 2>/dev/null || echo "✗ python-docx failed"
python3 -c "import openpyxl; print('✓ openpyxl installed')" 2>/dev/null || echo "✗ openpyxl failed"
python3 -c "import pptx; print('✓ python-pptx installed')" 2>/dev/null || echo "✗ python-pptx failed"
python3 -c "import mysql.connector; print('✓ mysql-connector-python installed')" 2>/dev/null || echo "✗ mysql-connector-python failed"

echo ""
echo "💡 If any installations failed, try running:"
echo "   pip3 install --user -r requirements.txt"
echo ""
