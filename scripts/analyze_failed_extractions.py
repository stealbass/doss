#!/usr/bin/env python3
"""
Analyse les documents legal_documents pour identifier ceux sans extraction
et recommande les stratégies de traitement selon leur taille.
"""

import sys
import os

try:
    import mysql.connector
except ImportError:
    print("ERROR: mysql-connector-python not installed")
    print("Install: pip install mysql-connector-python")
    sys.exit(1)

# Load DB config from .env
def load_env():
    from pathlib import Path
    env_file = Path(__file__).parent.parent / '.env'
    config = {}
    if env_file.exists():
        with open(env_file, 'r') as f:
            for line in f:
                if '=' in line and not line.startswith('#'):
                    key, value = line.strip().split('=', 1)
                    config[key.strip()] = value.strip().strip('"').strip("'")
    return {
        'host': config.get('DB_HOST', 'localhost'),
        'user': config.get('DB_USERNAME', 'root'),
        'password': config.get('DB_PASSWORD', ''),
        'database': config.get('DB_DATABASE', 'dossy'),
        'port': int(config.get('DB_PORT', 3306)),
    }

def main():
    db_config = load_env()
    
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        
        # Trouver tous les documents sans texte extrait
        cursor.execute("""
            SELECT id, file_name, file_size
            FROM legal_documents
            WHERE (extracted_text IS NULL OR extracted_text = '' OR extracted_text_length IS NULL OR extracted_text_length = 0)
            ORDER BY file_size ASC
        """)
        
        docs = cursor.fetchall()
        
        if not docs:
            print("✓ Tous les documents ont été extraits!")
            return
        
        print(f"\n{'='*80}")
        print(f"ANALYSE: {len(docs)} documents sans texte extrait")
        print(f"{'='*80}\n")
        
        # Grouper par taille
        tiny = []    # < 1MB
        small = []   # 1-5MB
        medium = []  # 5-15MB
        large = []   # 15-30MB
        huge = []    # > 30MB
        
        for doc in docs:
            size_mb = (doc['file_size'] or 0) / (1024 * 1024)
            doc['size_mb'] = size_mb
            
            if size_mb < 1:
                tiny.append(doc)
            elif size_mb < 5:
                small.append(doc)
            elif size_mb < 15:
                medium.append(doc)
            elif size_mb < 30:
                large.append(doc)
            else:
                huge.append(doc)
        
        # Afficher par catégorie
        def print_category(name, docs, strategy):
            if not docs:
                return
            print(f"\n{name} ({len(docs)} documents):")
            print(f"  Stratégie recommandée: {strategy}")
            print(f"  {'ID':<6} {'Taille':<10} Fichier")
            print(f"  {'-'*70}")
            for doc in docs:
                filename = doc['file_name'][:50] if doc['file_name'] else 'N/A'
                print(f"  {doc['id']:<6} {doc['size_mb']:>6.1f}MB   {filename}")
        
        print_category(
            "📄 TRÈS PETITS",
            tiny,
            "--ocr-pages=all --ocr-dpi=150 --ocr-delay=0"
        )
        
        print_category(
            "📄 PETITS",
            small,
            "--ocr-pages=all --ocr-dpi=140 --ocr-delay=0.3"
        )
        
        print_category(
            "📋 MOYENS",
            medium,
            "--ocr-pages=100 --ocr-dpi=120 --ocr-delay=0.8"
        )
        
        print_category(
            "📚 GRANDS",
            large,
            "--ocr-pages=60 --ocr-dpi=100 --ocr-delay=1.5"
        )
        
        print_category(
            "📚 TRÈS GRANDS",
            huge,
            "--ocr-pages=30 --ocr-dpi=80 --ocr-delay=3.0"
        )
        
        # Générer commandes batch
        print(f"\n{'='*80}")
        print("COMMANDES RECOMMANDÉES")
        print(f"{'='*80}\n")
        
        all_ids = [doc['id'] for doc in docs]
        
        print("# Extraction batch de tous les documents échoués:")
        print("export PYTHONPATH=/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/.local/lib/python3.7/site-packages\n")
        
        if tiny or small:
            ids = ','.join(str(d['id']) for d in tiny + small)
            print(f"# Petits fichiers (extraction complète):")
            print(f"for ID in {' '.join(str(d['id']) for d in tiny + small)}; do")
            print(f"  python3 scripts/extract_documents.py --source=legal --document-id=$ID --ignore-size --ocr-pages=all --ocr-dpi=140 --ocr-delay=0.3 --force-ocr")
            print(f"  sleep 3")
            print(f"done\n")
        
        if medium:
            print(f"# Fichiers moyens (100 pages):")
            print(f"for ID in {' '.join(str(d['id']) for d in medium)}; do")
            print(f"  python3 scripts/extract_documents.py --source=legal --document-id=$ID --ignore-size --ocr-pages=100 --ocr-dpi=120 --ocr-delay=0.8 --force-ocr")
            print(f"  sleep 5")
            print(f"done\n")
        
        if large:
            print(f"# Grands fichiers (60 pages, DPI réduit):")
            print(f"for ID in {' '.join(str(d['id']) for d in large)}; do")
            print(f"  python3 scripts/extract_documents.py --source=legal --document-id=$ID --ignore-size --ocr-pages=60 --ocr-dpi=100 --ocr-delay=1.5 --force-ocr")
            print(f"  sleep 8")
            print(f"done\n")
        
        if huge:
            print(f"# Très grands fichiers (30 pages, traitement léger):")
            print(f"for ID in {' '.join(str(d['id']) for d in huge)}; do")
            print(f"  python3 scripts/extract_documents.py --source=legal --document-id=$ID --ignore-size --ocr-pages=30 --ocr-dpi=80 --ocr-delay=3.0 --force-ocr")
            print(f"  sleep 10")
            print(f"done\n")
        
        print(f"\n# Pour traiter TOUS les documents restants avec le script batch:")
        print(f"bash scripts/batch_extract_failed.sh\n")
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"ERROR: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()
