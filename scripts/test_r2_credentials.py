#!/usr/bin/env python3
"""
Test R2 credentials from database
Vérifie que les credentials R2 sont correctement lus depuis la table utilities
"""

import sys
import mysql.connector
from pathlib import Path

def test_r2_credentials():
    """Test de lecture des credentials R2 depuis la base de données"""
    
    # Lire la configuration .env
    env_file = Path(__file__).parent.parent / '.env'
    config = {}
    
    if env_file.exists():
        with open(env_file, 'r') as f:
            for line in f:
                if '=' in line and not line.startswith('#'):
                    key, value = line.strip().split('=', 1)
                    config[key.strip()] = value.strip().strip('"').strip("'")
    
    db_config = {
        'host': config.get('DB_HOST', 'localhost'),
        'user': config.get('DB_USERNAME', 'root'),
        'password': config.get('DB_PASSWORD', ''),
        'database': config.get('DB_DATABASE', 'dossy'),
    }
    
    print("=" * 60)
    print("TEST: Lecture credentials R2 depuis base de données")
    print("=" * 60)
    print()
    
    try:
        print("✅ Connexion à la base de données...")
        connection = mysql_connector.connect(**db_config)
        cursor = connection.cursor(dictionary=True)
        
        print("✅ Lecture des credentials R2...")
        cursor.execute("""
            SELECT `name`, `value` 
            FROM settings 
            WHERE `name` IN ('storage_setting', 'r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint')
        """)
        
        settings = {row['name']: row['value'] for row in cursor.fetchall()}
        connection.close()
        
        print()
        print("📊 Credentials trouvés :")
        print("-" * 60)
        
        storage_setting = settings.get('storage_setting', 'NOT FOUND')
        r2_key = settings.get('r2_key', 'NOT FOUND')
        r2_secret = settings.get('r2_secret', 'NOT FOUND')
        r2_bucket = settings.get('r2_bucket', 'NOT FOUND')
        r2_endpoint = settings.get('r2_endpoint', 'NOT FOUND')
        
        print(f"storage_setting: {storage_setting}")
        print(f"r2_key: {r2_key[:20]}... (truncated)" if r2_key != 'NOT FOUND' and len(r2_key) > 20 else f"r2_key: {r2_key}")
        print(f"r2_secret: {r2_secret[:20]}... (truncated)" if r2_secret != 'NOT FOUND' and len(r2_secret) > 20 else f"r2_secret: {r2_secret}")
        print(f"r2_bucket: {r2_bucket}")
        print(f"r2_endpoint: {r2_endpoint}")
        print()
        
        if storage_setting == 'r2' and all([r2_key, r2_secret, r2_bucket, r2_endpoint]):
            print("✅ SUCCÈS: Tous les credentials R2 sont présents et complets")
            print()
            
            # Test connexion R2 avec boto3
            try:
                import boto3
                print("✅ boto3 est installé")
                print("🔄 Test de connexion à R2...")
                
                r2_endpoint_clean = r2_endpoint.replace('https://', '').replace('http://', '')
                s3 = boto3.client(
                    's3',
                    endpoint_url=f'https://{r2_endpoint_clean}',
                    aws_access_key_id=r2_key,
                    aws_secret_access_key=r2_secret,
                    region_name='auto'
                )
                
                # Test access to bucket
                s3.head_bucket(Bucket=r2_bucket)
                print(f"✅ SUCCÈS: Connexion R2 réussie - bucket '{r2_bucket}' accessible")
                
                # List some files
                print(f"📁 Listing des premiers fichiers dans le bucket...")
                response = s3.list_objects_v2(Bucket=r2_bucket, MaxKeys=5)
                if 'Contents' in response:
                    print(f"   Trouvé {len(response['Contents'])} fichier(s):")
                    for obj in response['Contents']:
                        print(f"   - {obj['Key']} ({obj['Size']} bytes)")
                else:
                    print("   (Bucket vide)")
                
                print()
                print("=" * 60)
                print("✅ TEST RÉUSSI - Les credentials R2 fonctionnent correctement")
                print("=" * 60)
                return True
                
            except ImportError:
                print("❌ ERREUR: boto3 n'est pas installé")
                print("   Installation: pip3 install boto3")
                return False
            except Exception as e:
                print(f"❌ ERREUR R2: {type(e).__name__}: {str(e)}")
                return False
        else:
            print("❌ ERREUR: Credentials R2 incomplets")
            missing = []
            if storage_setting != 'r2':
                missing.append(f"storage_setting est '{storage_setting}' (attendu: 'r2')")
            if not r2_key or r2_key == 'NOT FOUND':
                missing.append('r2_key')
            if not r2_secret or r2_secret == 'NOT FOUND':
                missing.append('r2_secret')
            if not r2_bucket or r2_bucket == 'NOT FOUND':
                missing.append('r2_bucket')
            if not r2_endpoint or r2_endpoint == 'NOT FOUND':
                missing.append('r2_endpoint')
            
            print(f"   Manquant: {', '.join(missing)}")
            return False
            
    except Exception as e:
        print(f"❌ ERREUR: {type(e).__name__}: {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == '__main__':
    success = test_r2_credentials()
    sys.exit(0 if success else 1)
