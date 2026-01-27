#!/usr/bin/env python3
"""
Document Extraction Script (IMPROVED - Auto-OCR pour PDFs scannés)
Stratégie robuste : essaie texte → essaie OCR simplifié → retourne ce qu'on peut
"""

import os
import sys
import json
import logging
import argparse
import subprocess
from pathlib import Path
from datetime import datetime

# Add python-packages directory to Python path
script_dir = Path(__file__).parent.parent
python_packages_dir = script_dir / 'python-packages'
if python_packages_dir.exists():
    sys.path.insert(0, str(python_packages_dir))
    # Also add standard location
    sys.path.insert(0, '/home/threesixty/yyy/Dossy/python-packages')
else:
    # Try absolute path if relative doesn't work
    sys.path.insert(0, '/home/threesixty/yyy/Dossy/python-packages')

try:
    import mysql.connector as mysql_connector
except ImportError:
    mysql_connector = None

logging.basicConfig(level=logging.INFO, format='[%(asctime)s] %(levelname)s: %(message)s')
logger = logging.getLogger(__name__)

# Imports optionnels
PyPDF2 = None
try:
    import PyPDF2
except ImportError:
    logger.warning("PyPDF2 not available")

pdfplumber = None
try:
    import pdfplumber
except ImportError:
    logger.warning("pdfplumber not available")

fitz = None  # PyMuPDF
try:
    import fitz
except ImportError:
    logger.warning("PyMuPDF (fitz) not available")

python_docx = None
try:
    from docx import Document
    python_docx = True
except ImportError:
    logger.warning("python-docx not available")

openpyxl = None
try:
    import openpyxl
except ImportError:
    logger.warning("openpyxl not available")

# Extraction limits to avoid OOM/timeouts on large PDFs
MAX_PAGES = int(os.getenv('EXTRACT_MAX_PAGES', '120'))  # max pages to process per document
MAX_OCR_PAGES = int(os.getenv('EXTRACT_MAX_OCR_PAGES', '6'))  # max pages to OCR per document
OCR_ZOOM = float(os.getenv('EXTRACT_OCR_ZOOM', '1.5'))  # scale factor for pixmap rendering


class DocumentExtractor:
    def __init__(self, file_path):
        self.file_path = Path(file_path)
        self.file_type = self.file_path.suffix.lower()

    def extract(self):
        """Extraire le texte de manière robuste"""
        logger.info(f"📄 Extracting: {self.file_path.name} ({self.file_type})")
        try:
            if self.file_type == '.pdf':
                return self._extract_pdf_robust()
            elif self.file_type in ['.docx', '.doc']:
                return self._extract_word()
            elif self.file_type in ['.xlsx', '.xls']:
                return self._extract_excel()
            elif self.file_type in ['.txt', '.md']:
                return self._extract_text()
            else:
                return None, f"Unsupported format: {self.file_type}"
        except Exception as e:
            logger.error(f"❌ Extraction error: {str(e)}")
            return None, str(e)

    def _extract_pdf_robust(self):
        """Extraction PDF robuste : texte → OCR basique"""
        try:
            all_text = []
            page_count = 0
            ocr_attempted = False
            ocr_count = 0
            
            # Essayer d'abord avec pdfplumber si disponible
            if pdfplumber:
                try:
                    with pdfplumber.open(self.file_path) as pdf:
                        page_count = len(pdf.pages)
                        page_limit = min(page_count, MAX_PAGES)
                        
                        for i, page in enumerate(pdf.pages, 1):
                            if i > page_limit:
                                break
                            # Essayer extraction texte d'abord
                            text = page.extract_text()
                            
                            if text and text.strip():
                                all_text.append(f"--- Page {i} ---\n{text}")
                                logger.debug(f"✅ Page {i}: Text extracted ({len(text)} chars)")
                            else:
                                # Si pas de texte, essayer OCR (limité)
                                if ocr_count < MAX_OCR_PAGES:
                                    ocr_text = self._ocr_page_tesseract(i)
                                    ocr_attempted = True
                                    if ocr_text:
                                        all_text.append(f"--- Page {i} (OCR) ---\n{ocr_text}")
                                        ocr_count += 1
                                        logger.info(f"🔤 Page {i}: OCR extracted ({len(ocr_text)} chars)")
                                else:
                                    logger.warning(f"⚠️ Page {i}: No text or OCR result")
                    
                    if all_text:
                        result = "\n\n".join(all_text)
                        logger.info(f"✅ PDF: extracted {page_count} pages ({len(result)} total chars)")
                        return result, None
                except Exception as e:
                    logger.warning(f"pdfplumber extraction failed: {e}, trying PyMuPDF...")
            
            # Fallback avec PyMuPDF si pdfplumber échoue
            if fitz:
                try:
                    pdf_doc = fitz.open(str(self.file_path))
                    page_count = pdf_doc.page_count
                    all_text = []
                    page_limit = min(page_count, MAX_PAGES)
                    
                    for page_num in range(page_limit):
                        page_obj = pdf_doc[page_num]
                        # Essayer extraire texte
                        text = page_obj.get_text("text")
                        
                        if text and text.strip():
                            all_text.append(f"--- Page {page_num + 1} ---\n{text}")
                            logger.debug(f"✅ Page {page_num + 1}: Text extracted ({len(text)} chars)")
                        else:
                            # Essayer OCR si pas de texte (limité)
                            if ocr_count < MAX_OCR_PAGES:
                                ocr_text = self._ocr_page_tesseract_with_doc(page_obj, page_num + 1)
                                ocr_attempted = True
                                if ocr_text:
                                    all_text.append(f"--- Page {page_num + 1} (OCR) ---\n{ocr_text}")
                                    ocr_count += 1
                                    logger.info(f"🔤 Page {page_num + 1}: OCR extracted ({len(ocr_text)} chars)")
                    
                    pdf_doc.close()
                    
                    if all_text:
                        result = "\n\n".join(all_text)
                        logger.info(f"✅ PDF: extracted {page_count} pages ({len(result)} total chars)")
                        return result, None
                except Exception as e:
                    logger.error(f"PyMuPDF extraction error: {e}")
            
            if not all_text:
                logger.warning(f"❌ No text extracted from {self.file_path.name}")
                return None, "No text extracted from PDF"
            
        except Exception as e:
            logger.error(f"PDF extraction error: {e}")
            return None, str(e)

    def _ocr_page_tesseract(self, page_num):
        """OCR d'une page using Tesseract directement (utilisé avec pdfplumber)"""
        try:
            if not fitz:
                return None
            
            temp_img = f"/tmp/page_{page_num}_ocr.png"
            
            # Ouvrir le PDF avec PyMuPDF et extraire l'image
            try:
                pdf_doc = fitz.open(str(self.file_path))
                page_obj = pdf_doc[page_num - 1]
                # Convertir en image PNG avec zoom configurable
                pix = page_obj.get_pixmap(matrix=fitz.Matrix(OCR_ZOOM, OCR_ZOOM))
                pix.save(temp_img)
                del pix
                pdf_doc.close()
            except Exception as e:
                logger.debug(f"PyMuPDF rendering failed for page {page_num}: {e}")
                return None
            
            # Utiliser Tesseract sur l'image
            try:
                result = subprocess.run(
                    ['tesseract', temp_img, 'stdout', '-l', 'fra+eng'],
                    capture_output=True,
                    text=True,
                    timeout=30
                )
                
                # Nettoyer
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                
                if result.returncode == 0 and result.stdout.strip():
                    return result.stdout.strip()
                else:
                    return None
            except subprocess.TimeoutExpired:
                logger.warning(f"Tesseract timeout for page {page_num}")
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                return None
            except Exception as e:
                logger.debug(f"Tesseract execution failed: {e}")
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                return None
        except Exception as e:
            logger.debug(f"OCR page {page_num} error: {e}")
            return None

    def _ocr_page_tesseract_with_doc(self, page_obj, page_num):
        """OCR d'une page using Tesseract (utilisé avec PyMuPDF document ouvert)"""
        try:
            temp_img = f"/tmp/page_{page_num}_ocr.png"
            
            # Convertir la page en image PNG avec zoom configurable
            try:
                pix = page_obj.get_pixmap(matrix=fitz.Matrix(OCR_ZOOM, OCR_ZOOM))
                pix.save(temp_img)
                del pix
            except Exception as e:
                logger.debug(f"PyMuPDF pixmap failed for page {page_num}: {e}")
                return None
            
            # Utiliser Tesseract sur l'image
            try:
                result = subprocess.run(
                    ['tesseract', temp_img, 'stdout', '-l', 'fra+eng'],
                    capture_output=True,
                    text=True,
                    timeout=30
                )
                
                # Nettoyer
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                
                if result.returncode == 0 and result.stdout.strip():
                    return result.stdout.strip()
                else:
                    return None
            except subprocess.TimeoutExpired:
                logger.warning(f"Tesseract timeout for page {page_num}")
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                return None
            except Exception as e:
                logger.debug(f"Tesseract execution failed: {e}")
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                return None
        except Exception as e:
            logger.debug(f"OCR page {page_num} error: {e}")
            return None

    def _ocr_page_tesseract_old(self, page, page_num):
        """OCR d'une page usando Tesseract directement"""
        try:
            temp_img = f"/tmp/page_{page_num}_ocr.png"
            
            # Essayer avec PyMuPDF d'abord (plus fiable)
            if fitz:
                try:
                    # Reconstruire le PDF avec fitz pour obtenir l'image de la page
                    pdf_doc = fitz.open(str(self.file_path))
                    page_obj = pdf_doc[page_num - 1]
                    # Convertir en image PNG
                    pix = page_obj.get_pixmap(matrix=fitz.Matrix(2, 2))  # 2x zoom
                    pix.save(temp_img)
                    pdf_doc.close()
                except Exception as e:
                    logger.debug(f"PyMuPDF rendering failed: {e}")
                    return None
            else:
                # Si PyMuPDF pas disponible, essayer pdfplumber (mais c'est lui qui a échoué)
                logger.warning(f"PyMuPDF not available for page {page_num} OCR")
                return None
            
            # Utiliser Tesseract sur l'image
            try:
                result = subprocess.run(
                    ['tesseract', temp_img, 'stdout', '-l', 'fra+eng'],
                    capture_output=True,
                    text=True,
                    timeout=30
                )
                
                # Nettoyer
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                
                if result.returncode == 0 and result.stdout.strip():
                    return result.stdout.strip()
                else:
                    return None
            except subprocess.TimeoutExpired:
                logger.warning(f"Tesseract timeout for page {page_num}")
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                return None
            except Exception as e:
                logger.warning(f"Tesseract execution failed: {e}")
                if os.path.exists(temp_img):
                    os.remove(temp_img)
                return None
        except Exception as e:
            logger.debug(f"OCR page {page_num} error: {e}")
            return None

    def _extract_word(self):
        """Extract from Word (.doc, .docx)"""
        if not python_docx:
            return None, "python-docx not installed"
        
        try:
            if self.file_type == '.docx':
                doc = Document(self.file_path)
                text = "\n".join([p.text for p in doc.paragraphs])
            else:
                # .doc files - try with python-docx if possible
                logger.warning("Old .doc format - trying with python-docx")
                doc = Document(self.file_path)
                text = "\n".join([p.text for p in doc.paragraphs])
            
            if text.strip():
                logger.info(f"✅ Word: extracted {len(text)} chars")
                return text, None
            else:
                logger.warning("No text extracted from Word document")
                return None, "Empty document"
        except Exception as e:
            logger.error(f"Word extraction error: {e}")
            return None, str(e)

    def _extract_excel(self):
        """Extract from Excel (.xlsx, .xls)"""
        if not openpyxl:
            return None, "openpyxl not installed"
        
        try:
            wb = openpyxl.load_workbook(self.file_path)
            all_text = []
            
            for sheet_name in wb.sheetnames:
                sheet = wb[sheet_name]
                all_text.append(f"\n--- Sheet: {sheet_name} ---\n")
                
                for row in sheet.iter_rows(values_only=True):
                    row_text = " | ".join(str(cell) if cell is not None else "" for cell in row)
                    if row_text.strip():
                        all_text.append(row_text)
            
            text = "\n".join(all_text)
            if text.strip():
                logger.info(f"✅ Excel: extracted {len(text)} chars")
                return text, None
            else:
                return None, "Empty workbook"
        except Exception as e:
            logger.error(f"Excel extraction error: {e}")
            return None, str(e)

    def _extract_text(self):
        """Extract from text files"""
        try:
            with open(self.file_path, 'r', encoding='utf-8') as f:
                text = f.read()
            if text.strip():
                logger.info(f"✅ Text file: extracted {len(text)} chars")
                return text, None
            return None, "Empty file"
        except Exception as e:
            logger.error(f"Text extraction error: {e}")
            return None, str(e)


def get_document_file_generic(doc, db_config, source):
    """Récupérer le fichier depuis local ou R2"""
    file_path_db = doc.get('file_path', '') or ''
    file_name = doc.get('file_name', '') or Path(file_path_db).name
    
    logger.info(f"🔍 Searching for file: {file_name}")
    
    # Essayer local d'abord
    base_paths = [
        Path(__file__).parent.parent / 'storage' / 'app' / 'public' / file_path_db,
        Path(__file__).parent.parent / 'storage' / 'app' / file_path_db,
        Path(__file__).parent.parent / 'storage' / 'uploads' / file_name,
    ]
    
    for local_path in base_paths:
        if local_path.exists():
            logger.info(f"✅ File found locally: {local_path}")
            return local_path, False
    
    # Essayer R2
    try:
        if not mysql_connector:
            logger.error('mysql.connector not available')
            return None, False
            
        logger.info("Connecting to database for R2 credentials...")
        connection = mysql_connector.connect(**db_config)
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT `name`, `value` FROM settings WHERE `name` IN ('storage_setting', 'r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint')")
        settings = {row['name']: row['value'] for row in cursor.fetchall()}
        connection.close()
        
        logger.info(f"✅ R2 config loaded")
        
        storage_setting = settings.get('storage_setting', 'local')
        if storage_setting == 'r2':
            r2_key = settings.get('r2_key', '')
            r2_secret = settings.get('r2_secret', '')
            r2_bucket = settings.get('r2_bucket', '')
            r2_endpoint = settings.get('r2_endpoint', '')
            
            if r2_key and r2_secret and r2_bucket and r2_endpoint:
                try:
                    import boto3
                    storage_path = file_path_db or file_name
                    r2_endpoint_clean = r2_endpoint.replace('https://', '').replace('http://', '')
                    
                    logger.info(f"Creating S3 client...")
                    s3 = boto3.client(
                        's3',
                        endpoint_url=f'https://{r2_endpoint_clean}',
                        aws_access_key_id=r2_key,
                        aws_secret_access_key=r2_secret,
                        region_name='auto'
                    )
                    
                    temp_dir = Path(__file__).parent.parent / 'storage' / 'temp'
                    temp_dir.mkdir(parents=True, exist_ok=True)
                    temp_path = temp_dir / file_name
                    
                    logger.info(f"Downloading from R2: {storage_path}")
                    s3.download_file(r2_bucket, storage_path, str(temp_path))
                    logger.info(f"✅ File downloaded from R2")
                    return temp_path, True
                except Exception as e:
                    logger.error(f"R2 download error: {e}")
    except Exception as e:
        logger.error(f"Database error: {e}")
    
    logger.error(f"File not found")
    return None, False


def fetch_documents(db, source, document_id=None):
    """Récupérer les documents à traiter"""
    cursor = db.connection.cursor(dictionary=True)
    
    sources_config = {
        'legal': ('legal_documents', ['id', 'file_path', 'file_name', 'file_size']),
        'template': ('document_templates', ['id', 'file_path', 'file_name', 'file_size']),
        'fiscal': ('fiscal_social_resources', ['id', 'file_path', 'file_name', 'file_size']),
    }
    
    if source not in sources_config:
        raise ValueError(f"Unknown source: {source}")
    
    table, columns = sources_config[source]
    
    if document_id:
        query = f"SELECT {', '.join(columns)} FROM {table} WHERE id = %s"
        cursor.execute(query, (document_id,))
    else:
        query = f"SELECT {', '.join(columns)} FROM {table} WHERE (extracted_text IS NULL OR extracted_text = '') LIMIT 50"
        cursor.execute(query)
    
    return cursor.fetchall()


def update_document(db, source, doc_id, extracted_text):
    """Mettre à jour le document avec le texte extrait"""
    cursor = db.connection.cursor()
    
    if extracted_text:
        query = f"UPDATE {source}_documents SET extracted_text = %s WHERE id = %s"
        cursor.execute(query, (extracted_text, doc_id))
        db.connection.commit()
        logger.info(f"✅ {source}_documents id {doc_id} updated")
    else:
        logger.warning(f"No text to save for {source} id {doc_id}")


def main():
    parser = argparse.ArgumentParser(description='Extract text from documents')
    parser.add_argument('--document-id', type=int, help='Document ID')
    parser.add_argument('--source', choices=['legal', 'template', 'fiscal'], default='legal')
    args = parser.parse_args()
    
    logger.info(f"🚀 Start document processing (source={args.source})")
    
    # Config DB
    # Align with Laravel .env naming (DB_USERNAME/DB_PASSWORD/DB_HOST)
    db_config = {
        'host': os.getenv('DB_HOST') or os.getenv('MYSQL_HOST') or 'localhost',
        'user': os.getenv('DB_USERNAME') or os.getenv('DB_USER') or 'root',
        'password': os.getenv('DB_PASSWORD', ''),
        'database': os.getenv('DB_DATABASE') or os.getenv('MYSQL_DATABASE') or 'dossy',
    }
    
    try:
        import mysql.connector
        connection = mysql.connector.connect(**db_config)
        logger.info("✅ Connected to database")
    except Exception as e:
        logger.error(f"Database connection error: {e}")
        return
    
    class DB:
        def __init__(self, conn):
            self.connection = conn
    
    db = DB(connection)
    
    # Récupérer documents
    try:
        documents = fetch_documents(db, args.source, args.document_id)
        logger.info(f"📚 {len(documents)} document(s) to process")
        
        success_count = 0
        error_count = 0
        
        for doc in documents:
            doc_id = doc['id']
            logger.info(f"Processing: {doc.get('file_name', 'unknown')} (ID: {doc_id})")
            
            try:
                # Récupérer le fichier
                file_path, is_temp = get_document_file_generic(doc, db_config, args.source)
                
                if not file_path:
                    logger.error(f"File not found for {args.source} id {doc_id}")
                    error_count += 1
                    continue
                
                # Extraire le texte
                extractor = DocumentExtractor(file_path)
                extracted_text, error = extractor.extract()
                
                # Mettre à jour
                if extracted_text:
                    update_table = {
                        'legal': 'legal_documents',
                        'template': 'document_templates',
                        'fiscal': 'fiscal_social_resources'
                    }[args.source]
                    
                    cursor = db.connection.cursor()
                    cursor.execute(
                        f"UPDATE {update_table} SET extracted_text = %s WHERE id = %s",
                        (extracted_text[:1000000], doc_id)  # Limiter à 1MB
                    )
                    db.connection.commit()
                    logger.info(f"✅ {update_table} id {doc_id} updated with {len(extracted_text)} chars")
                    success_count += 1
                else:
                    logger.warning(f"❌ No text extracted from {args.source} id {doc_id}")
                    error_count += 1
                
                # Nettoyer temp
                if is_temp and file_path and Path(file_path).exists():
                    Path(file_path).unlink()
                    logger.info(f"Temp file removed")
            
            except Exception as doc_error:
                logger.error(f"⚠️ Skipping document {doc_id} due to error: {str(doc_error)}")
                error_count += 1
                continue
        
        logger.info(f"📊 Résumé: {success_count} réussis, {error_count} erreurs")
    
    finally:
        if connection:
            connection.close()
    
    logger.info("✅ Processing finished")


if __name__ == '__main__':
    main()
