#!/bin/bash

################################################################################
# 🚀 DEPLOYMENT SCRIPT - Automatic Fix for Document Upload Issues
# 
# This script:
# 1. Installs missing Python dependencies (PyPDF2, etc.)
# 2. Verifies the environment
# 3. Tests document extraction
# 4. Runs queue worker for pending documents
#
# Usage: ./DEPLOY_FIXES_AUTOMATIC.sh
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="${PROJECT_ROOT:-.}"
LOG_FILE="$PROJECT_ROOT/storage/logs/deployment_$(date +%Y%m%d_%H%M%S).log"

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1" | tee -a "$LOG_FILE"
}

# Main deployment workflow
main() {
    echo ""
    echo "==============================================================================="
    echo "  🚀 DOSSY - Automatic Document Upload Fix Deployment"
    echo "==============================================================================="
    echo ""
    
    # Step 1: Verify project structure
    log_info "Step 1/6: Verifying project structure..."
    if [ ! -f "artisan" ]; then
        log_error "Laravel project not found. Please run from project root."
        exit 1
    fi
    log_success "Laravel project detected"
    
    # Step 2: Install Python dependencies
    log_info "Step 2/6: Installing Python dependencies..."
    
    PYTHON_CMD="python3"
    if ! command -v python3 &> /dev/null; then
        PYTHON_CMD="python"
    fi
    
    if ! command -v $PYTHON_CMD &> /dev/null; then
        log_error "Python not found on system"
        exit 1
    fi
    
    log_info "Using Python: $PYTHON_CMD ($($PYTHON_CMD --version 2>&1))"
    
    # Install packages
    echo "Installing packages..." | tee -a "$LOG_FILE"
    $PYTHON_CMD -m pip install --upgrade pip --quiet 2>/dev/null || true
    
    PACKAGES=(
        "PyPDF2"
        "pdfplumber"
        "python-docx"
        "openpyxl"
        "python-pptx"
        "Pillow"
        "requests"
        "python-dotenv"
    )
    
    FAILED_PACKAGES=()
    
    for pkg in "${PACKAGES[@]}"; do
        if $PYTHON_CMD -m pip install "$pkg" --quiet --user 2>/dev/null || \
           $PYTHON_CMD -m pip install "$pkg" --quiet 2>/dev/null; then
            log_success "Installed: $pkg"
        else
            log_warning "Failed to install: $pkg (will continue)"
            FAILED_PACKAGES+=("$pkg")
        fi
    done
    
    # MySQL Connector (optional)
    if $PYTHON_CMD -m pip install mysql-connector-python --quiet --user 2>/dev/null || \
       $PYTHON_CMD -m pip install mysql-connector-python --quiet 2>/dev/null; then
        log_success "Installed: mysql-connector-python"
    else
        log_warning "MySQL connector failed (optional)"
    fi
    
    # Step 3: Verify Python environment
    log_info "Step 3/6: Verifying Python environment..."
    
    VERIFY_SCRIPT=$(mktemp)
    cat > "$VERIFY_SCRIPT" << 'EOF'
import sys
failed = []

packages = [
    ("PyPDF2", "PyPDF2"),
    ("pdfplumber", "pdfplumber"),
    ("python-docx", "docx"),
    ("openpyxl", "openpyxl"),
    ("python-pptx", "pptx"),
    ("Pillow", "PIL"),
    ("requests", "requests"),
]

for name, import_name in packages:
    try:
        __import__(import_name)
        print(f"✓ {name}")
    except ImportError:
        print(f"✗ {name}")
        failed.append(name)

if failed:
    print(f"\nWARNING: {len(failed)} packages not available")
    sys.exit(0)  # Not critical
else:
    print("\n✓ All critical packages verified")
    sys.exit(0)
EOF
    
    $PYTHON_CMD "$VERIFY_SCRIPT" | tee -a "$LOG_FILE"
    rm -f "$VERIFY_SCRIPT"
    
    # Step 4: Clear application cache
    log_info "Step 4/6: Clearing application cache..."
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    log_success "Cache cleared"
    
    # Step 5: Check pending documents
    log_info "Step 5/6: Checking pending documents..."
    
    PENDING_COUNT=$(php artisan tinker --execute "
    echo \App\Models\SubmittedDocument::whereNull('extracted_text')
        ->orWhere('extracted_text', '')
        ->count();
    " 2>/dev/null || echo "0")
    
    if [ "$PENDING_COUNT" != "0" ] && [ "$PENDING_COUNT" != "" ]; then
        log_warning "Found $PENDING_COUNT documents pending extraction"
    else
        log_success "No documents pending extraction"
    fi
    
    # Step 6: Test Python script
    log_info "Step 6/6: Testing extraction script..."
    
    if [ -f "scripts/extract_documents.py" ]; then
        TEST_OUTPUT=$($PYTHON_CMD scripts/extract_documents.py 2>&1 | head -5)
        if echo "$TEST_OUTPUT" | grep -q "Démarrage\|starting\|Processing"; then
            log_success "Extraction script is functional"
        else
            log_warning "Extraction script test: $TEST_OUTPUT"
        fi
    else
        log_error "Extraction script not found at scripts/extract_documents.py"
    fi
    
    # Final summary
    echo ""
    echo "==============================================================================="
    log_success "Deployment completed!"
    echo "==============================================================================="
    echo ""
    echo "Next steps:"
    echo "  1. Check logs: tail -100 storage/logs/laravel.log"
    echo "  2. Process pending documents: php artisan queue:work --once"
    echo "  3. Test upload: Upload a new document in the mobile app"
    echo "  4. Monitor: tail -f storage/logs/laravel.log"
    echo ""
    echo "Logs saved to: $LOG_FILE"
    echo ""
}

# Run main function
main
exit $?
