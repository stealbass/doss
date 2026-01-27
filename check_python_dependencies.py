#!/usr/bin/env python3
"""
Check and report missing Python dependencies for document extraction
Run: python3 check_python_dependencies.py
"""

import sys
import importlib.util
from pathlib import Path

# Color codes for terminal output
GREEN = '\033[92m'
RED = '\033[91m'
YELLOW = '\033[93m'
BOLD = '\033[1m'
RESET = '\033[0m'

# Required modules with pip install names
REQUIRED_MODULES = {
    'pdfplumber': 'pdfplumber',
    'docx': 'python-docx',
    'openpyxl': 'openpyxl',
    'pptx': 'python-pptx',
    'boto3': 'boto3',
    'mysql': 'mysql-connector-python',
}

def check_module(module_name):
    """Check if a Python module is installed"""
    try:
        importlib.import_module(module_name)
        return True
    except ImportError:
        return False

def main():
    print(f"\n{BOLD}=== Python Dependencies Check ==={RESET}\n")
    
    missing = []
    installed = []
    
    for module_name, pip_name in REQUIRED_MODULES.items():
        if check_module(module_name):
            print(f"{GREEN}✅{RESET} {module_name:20} (install: {pip_name})")
            installed.append(pip_name)
        else:
            print(f"{RED}❌{RESET} {module_name:20} (install: {pip_name})")
            missing.append(pip_name)
    
    print(f"\n{BOLD}Summary:{RESET}")
    print(f"  Installed: {len(installed)}/{len(REQUIRED_MODULES)}")
    print(f"  Missing:   {len(missing)}/{len(REQUIRED_MODULES)}")
    
    if missing:
        print(f"\n{RED}{BOLD}ACTION REQUIRED:{RESET}")
        print(f"\nInstall missing dependencies with:")
        print(f"\n  {YELLOW}pip3 install {' '.join(missing)}{RESET}")
        print(f"\n  OR")
        print(f"\n  {YELLOW}pip install {' '.join(missing)}{RESET}")
        print()
        return 1
    else:
        print(f"\n{GREEN}{BOLD}All dependencies are installed! ✅{RESET}\n")
        return 0

if __name__ == '__main__':
    sys.exit(main())
