#!/usr/bin/env python3
"""
Document Extraction Script (multi-source)
Extracts text from PDF, Word, Excel, PowerPoint, text, and images (OCR).
Updates submitted_documents, legal_documents, document_templates, fiscal_social_resources.
"""

import os
import sys
import json
import logging
import argparse
from pathlib import Path
from datetime import datetime

# Optional DB driver
try:
    import mysql.connector as mysql_connector
except ImportError:
    mysql_connector = None

logging.basicConfig(level=logging.INFO, format='[%(asctime)s] %(levelname)s: %(message)s')
logger = logging.getLogger(__name__)


def try_import(package_name, import_name=None):
    """Safe import with warning instead of crash."""
    try:
        if import_name:
            return __import__(import_name)
        return __import__(package_name)
    except ImportError:
        logger.warning(f"Module '{package_name}' not available. Install if needed.")
        return None


PyPDF2 = try_import('PyPDF2')
pdfplumber = try_import('pdfplumber')
python_docx = try_import('python_docx', 'docx')
openpyxl = try_import('openpyxl')
pptx = try_import('pptx')


class DocumentExtractor:
    def __init__(self, file_path, r2_config=None, ocr_max_pages: int = 10, ocr_lang: str = 'fra+eng', ocr_dpi: int = 150, force_ocr: bool = False, ocr_page_delay: float = 0.0):
        self.file_path = Path(file_path)
        self.file_type = self.file_path.suffix.lower()
        self.r2_config = r2_config or {}
        # OCR options
        # ocr_max_pages: -1 means all pages
        self.ocr_max_pages = ocr_max_pages
        self.ocr_lang = ocr_lang
        self.ocr_dpi = ocr_dpi
        self.force_ocr = force_ocr
        self.ocr_page_delay = ocr_page_delay  # seconds to wait between pages (reduce memory pressure)

    def extract(self):
        logger.info(f"Extracting: {self.file_path.name}")
        try:
            if self.file_type == '.pdf':
                return self._extract_pdf()
            if self.file_type in ['.docx', '.doc']:
                return self._extract_word()
            if self.file_type in ['.xlsx', '.xls']:
                return self._extract_excel()
            if self.file_type in ['.pptx', '.ppt']:
                return self._extract_powerpoint()
            if self.file_type in ['.txt', '.md']:
                return self._extract_text()
            if self.file_type in ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp', '.tiff', '.tif']:
                return self._extract_image()
            return None, f"Unsupported format: {self.file_type}"
        except Exception as e:
            logger.error(f"Extraction error: {str(e)}")
            return None, str(e)

    def _extract_pdf(self):
        import gc
        if pdfplumber:
            try:
                with pdfplumber.open(self.file_path) as pdf:
                    content = []
                    ocr_attempted = False
                    max_ocr_pages = self.ocr_max_pages if isinstance(self.ocr_max_pages, int) else 10
                    for i, page in enumerate(pdf.pages, 1):
                        # Check if we should OCR this page
                        should_ocr = self.force_ocr
                        
                        # Try to extract text first
                        text = page.extract_text()
                        
                        # If no text AND not force_ocr, try OCR
                        if not text or not text.strip():
                            should_ocr = True
                        
                        # Add extracted text if available and not forcing OCR
                        if text and text.strip() and not self.force_ocr:
                            content.append(f"--- Page {i} ---\n{text}")
                        
                        # Now try OCR if needed
                        if should_ocr:
                            can_ocr = True
                            if isinstance(self.ocr_max_pages, int) and self.ocr_max_pages >= 0:
                                can_ocr = i <= self.ocr_max_pages
                            # If ocr_max_pages == -1, OCR all pages
                            if can_ocr:
                                ocr_text = self._extract_image_from_pdf_page(page, i)
                                ocr_attempted = True
                                if ocr_text and ocr_text.strip():
                                    if self.force_ocr and not text:
                                        # If forcing OCR and no text, use OCR result directly
                                        content.append(f"--- Page {i} (OCR) ---\n{ocr_text}")
                                    elif not text or not text.strip():
                                        # If no text extracted, use OCR
                                        content.append(f"--- Page {i} (OCR) ---\n{ocr_text}")
                                    else:
                                        # Have both text and OCR, prefer OCR if forcing
                                        if self.force_ocr:
                                            content.append(f"--- Page {i} (OCR) ---\n{ocr_text}")
                                # Force GC after each OCR page
                                gc.collect()
                            else:
                                logger.debug(f"Page {i}: Skipping OCR (limit {max_ocr_pages} reached)")
                    full_text = "\n".join(content)
                    # Final cleanup
                    gc.collect()
                    limit_desc = 'all' if self.ocr_max_pages == -1 else max_ocr_pages
                    logger.info(f"PDF: extracted {len(pdf.pages)} pages (OCR attempted: {ocr_attempted}, limit: {limit_desc})")
                    return full_text, None
            except Exception as e:
                return None, f"PDF error (pdfplumber): {str(e)}"

        if PyPDF2:
            try:
                with open(self.file_path, 'rb') as f:
                    reader = PyPDF2.PdfReader(f)
                    content = []
                    for i, page in enumerate(reader.pages, 1):
                        text = page.extract_text()
                        if text and text.strip():
                            content.append(f"--- Page {i} ---\n{text}")
                    full_text = "\n".join(content)
                    logger.info(f"PDF: extracted {len(reader.pages)} pages")
                    return full_text, None
            except Exception as e:
                return None, f"PDF error (PyPDF2): {str(e)}"

        return None, "No PDF library available. Install pdfplumber or PyPDF2"

    def _extract_image_from_pdf_page(self, page, page_num: int) -> str:
        """Convert PDF page to image and extract text via OCR."""
        import gc
        import time
        try:
            import pytesseract
            from PIL import Image
            img = None
            
            # Method 1: Try PyMuPDF (fitz) - optimized for large files
            try:
                import fitz
                # Open PDF only for this page, close immediately after
                pdf_doc = fitz.open(str(self.file_path))
                try:
                    if page_num <= len(pdf_doc):
                        pdf_page = pdf_doc[page_num - 1]
                        # Render with configurable DPI via matrix scale
                        scale = max(1.0, float(self.ocr_dpi) / 100.0)
                        pix = pdf_page.get_pixmap(matrix=fitz.Matrix(scale, scale), alpha=False)
                        img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)
                        logger.debug(f"Page {page_num}: Rendered via PyMuPDF")
                        # Explicitly free memory
                        del pix
                finally:
                    pdf_doc.close()
                    del pdf_doc
                    gc.collect()
            except ImportError:
                logger.debug("PyMuPDF not available, trying pdf2image")
            except MemoryError:
                logger.warning(f"PyMuPDF memory error on page {page_num}. Trying pdf2image...")
                img = None
            except Exception as e:
                logger.warning(f"PyMuPDF rendering failed: {e}")
            
            # Method 2: Fallback to pdf2image
            if img is None:
                try:
                    from pdf2image import convert_from_path
                    images = convert_from_path(str(self.file_path), first_page=page_num, last_page=page_num, dpi=max(72, self.ocr_dpi))
                    if images:
                        img = images[0]
                        logger.debug(f"Page {page_num}: Rendered via pdf2image")
                except ImportError:
                    logger.warning("pdf2image not installed")
                except MemoryError:
                    logger.error(f"Memory error converting page {page_num}. File may be too large.")
                    return ''
                except Exception as e:
                    logger.warning(f"pdf2image failed: {e}")
            
            # If we got an image, run OCR
            if img is not None:
                # Configure Tesseract
                try:
                    tcmd = getattr(pytesseract.pytesseract, 'tesseract_cmd', None)
                    if not tcmd or not Path(tcmd).exists():
                        for p in ['/usr/bin/tesseract', '/usr/local/bin/tesseract', '/opt/homebrew/bin/tesseract']:
                            if Path(p).exists():
                                pytesseract.pytesseract.tesseract_cmd = p
                                break
                except Exception:
                    pass
                
                # Get best language (fallback to provided config)
                lang = self.ocr_lang or 'fra+eng'
                try:
                    langs = set(pytesseract.get_languages(config=''))
                    has_fra = 'fra' in langs
                    has_eng = 'eng' in langs
                    if has_fra and has_eng:
                        lang = self.ocr_lang if '+' in (self.ocr_lang or '') else 'fra+eng'
                    elif has_fra:
                        lang = 'fra' if 'fra' in (self.ocr_lang or '') else 'fra'
                    elif has_eng:
                        lang = 'eng' if 'eng' in (self.ocr_lang or '') else 'eng'
                except Exception:
                    pass
                
                text = pytesseract.image_to_string(img, lang=lang)
                # Free image memory
                del img
                gc.collect()
                
                if text and text.strip():
                    logger.info(f"Page {page_num} OCR ({lang}): {len(text)} chars extracted")
                    # Force aggressive GC and optional sleep to prevent OOM
                    gc.collect()
                    if hasattr(self, 'ocr_page_delay') and self.ocr_page_delay > 0:
                        time.sleep(self.ocr_page_delay)
                    return text
                else:
                    # Even if no text, release memory
                    gc.collect()
            else:
                logger.warning(f"Page {page_num}: Could not render (needs PyMuPDF or pdf2image)")
                
        except Exception as e:
            logger.warning(f"OCR fallback error for page {page_num}: {e}")
        return ''


    def _extract_word(self):
        if not python_docx:
            return None, "python-docx not installed. Install: pip install python-docx"
        try:
            doc = python_docx.Document(self.file_path)
            content = []
            for para in doc.paragraphs:
                if para.text.strip():
                    content.append(para.text)
            for table in doc.tables:
                content.append("\n--- Table ---")
                for row in table.rows:
                    row_text = " | ".join(cell.text for cell in row.cells)
                    content.append(row_text)
            full_text = "\n".join(content)
            logger.info(f"Word: extracted {len(doc.paragraphs)} paragraphs")
            return full_text, None
        except Exception as e:
            return None, f"Word error: {str(e)}"

    def _extract_excel(self):
        if not openpyxl:
            return None, "openpyxl not installed. Install: pip install openpyxl"
        try:
            wb = openpyxl.load_workbook(self.file_path)
            content = []
            for sheet_name in wb.sheetnames:
                sheet = wb[sheet_name]
                content.append(f"\n=== Sheet: {sheet_name} ===")
                for row in sheet.iter_rows(values_only=True):
                    row_text = " | ".join(str(cell or "") for cell in row)
                    if row_text.strip():
                        content.append(row_text)
            full_text = "\n".join(content)
            logger.info(f"Excel: extracted {len(wb.sheetnames)} sheets")
            return full_text, None
        except Exception as e:
            return None, f"Excel error: {str(e)}"

    def _extract_powerpoint(self):
        if not pptx:
            return None, "python-pptx not installed. Install: pip install python-pptx"
        try:
            prs = pptx.Presentation(self.file_path)
            content = []
            for i, slide in enumerate(prs.slides, 1):
                content.append(f"\n--- Slide {i} ---")
                for shape in slide.shapes:
                    if hasattr(shape, 'text') and shape.text.strip():
                        content.append(shape.text)
                    elif getattr(shape, 'has_table', False):
                        table = shape.table
                        for row in table.rows:
                            row_text = " | ".join(cell.text for cell in row.cells)
                            content.append(row_text)
            full_text = "\n".join(content)
            logger.info(f"PowerPoint: extracted {len(prs.slides)} slides")
            return full_text, None
        except Exception as e:
            return None, f"PowerPoint error: {str(e)}"

    def _extract_text(self):
        try:
            with open(self.file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            logger.info(f"Text: extracted {len(content)} chars")
            return content, None
        except Exception as e:
            return None, f"Text error: {str(e)}"

    def _extract_image(self):
        pytesseract_module = try_import('pytesseract')
        PIL = try_import('PIL')
        if not pytesseract_module or not PIL:
            return None, "pytesseract or Pillow not installed. Install: pip install pytesseract pillow"
        try:
            from PIL import Image
            import pytesseract
            # Configure Tesseract binary path if not in PATH
            try:
                tcmd = getattr(pytesseract.pytesseract, 'tesseract_cmd', None)
                if not tcmd or not Path(tcmd).exists():
                    for p in ['/usr/bin/tesseract', '/usr/local/bin/tesseract', '/opt/homebrew/bin/tesseract']:
                        if Path(p).exists():
                            pytesseract.pytesseract.tesseract_cmd = p
                            logger.info(f"Tesseract binary set to: {p}")
                            break
                # Set TESSDATA_PREFIX if language data directory is detected
                if not os.environ.get('TESSDATA_PREFIX'):
                    for td in ['/usr/share/tesseract-ocr/4.00/tessdata', '/usr/share/tesseract/tessdata']:
                        if Path(td).exists():
                            os.environ['TESSDATA_PREFIX'] = str(Path(td).parent)
                            logger.info(f"TESSDATA_PREFIX set to: {os.environ['TESSDATA_PREFIX']}")
                            break
            except Exception as cfg_err:
                logger.warning(f"Tesseract configuration warning: {cfg_err}")
            # Determine best language combo
            lang = 'fra+eng'
            try:
                langs = set(pytesseract.get_languages(config=''))
                has_fra = 'fra' in langs
                has_eng = 'eng' in langs
                if has_fra and has_eng:
                    lang = 'fra+eng'
                elif has_fra:
                    lang = 'fra'
                elif has_eng:
                    lang = 'eng'
                else:
                    logger.warning("No 'fra' or 'eng' languages reported by Tesseract; using default 'fra+eng'")
            except Exception:
                # Keep default
                pass
            img = Image.open(self.file_path)
            text = pytesseract.image_to_string(img, lang=lang)
            if not text or text.strip() == '':
                logger.warning("Image: no text detected")
                return '', None
            logger.info(f"Image OCR: extracted {len(text)} chars (lang={lang})")
            return text, None
        except Exception as e:
            return None, f"Image OCR error: {str(e)}"


class DatabaseUpdater:
    def __init__(self, db_config=None):
        self.db_config = db_config or self._load_env_config()
        self.connection = None

    def _load_env_config(self):
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

    def connect(self):
        try:
            if not mysql_connector:
                logger.error('mysql-connector-python not installed')
                return False
            self.connection = mysql_connector.connect(**self.db_config)
            logger.info('Connected to database')
            return True
        except Exception as e:
            logger.error(f"DB connection error: {str(e)}")
            return False

    def update_document(self, document_id, extracted_text, metadata=None):
        if not self.connection:
            return False, 'No DB connection'
        try:
            cursor = self.connection.cursor()
            query = """
                UPDATE submitted_documents 
                SET extracted_text = %s,
                    extracted_text_length = %s,
                    processing_status = 'completed',
                    processed_at = NOW(),
                    metadata = %s
                WHERE id = %s
            """
            metadata_json = json.dumps(metadata or {})
            cursor.execute(query, (
                extracted_text,
                len(extracted_text),
                metadata_json,
                document_id,
            ))
            self.connection.commit()
            logger.info(f"Document {document_id} updated")
            return True, None
        except Exception as e:
            self.connection.rollback()
            logger.error(f"DB UPDATE error: {str(e)}")
            return False, str(e)

    def mark_failed(self, document_id, error_message):
        if not self.connection:
            return False
        try:
            cursor = self.connection.cursor()
            query = """
                UPDATE submitted_documents 
                SET processing_status = 'failed',
                    processing_error = %s
                WHERE id = %s
            """
            cursor.execute(query, (error_message, document_id))
            self.connection.commit()
            logger.info(f"Document {document_id} marked failed")
            return True
        except Exception as e:
            logger.error(f"mark_failed error: {str(e)}")
            return False

    def get_unprocessed_documents(self):
        if not self.connection:
            return []
        try:
            cursor = self.connection.cursor(dictionary=True)
            cursor.execute(
                """
                SELECT id, original_filename, stored_filename, storage_path
                FROM submitted_documents
                WHERE processing_status = 'pending'
                LIMIT 10
                """
            )
            return cursor.fetchall()
        except Exception as e:
            logger.error(f"get_unprocessed error: {str(e)}")
            return []

    def update_library_document(self, source, document_id, extracted_text):
        if not self.connection:
            return False
        table_map = {
            'legal': 'legal_documents',
            'template': 'document_templates',
            'fiscal': 'fiscal_social_resources',
        }
        table = table_map.get(source)
        if not table:
            logger.error(f"Unknown source: {source}")
            return False
        try:
            cursor = self.connection.cursor()
            query = f"""
                UPDATE {table}
                SET extracted_text = %s,
                    extracted_text_length = %s,
                    updated_at = NOW()
                WHERE id = %s
            """
            cursor.execute(query, (extracted_text, len(extracted_text), document_id))
            self.connection.commit()
            logger.info(f"{table} id {document_id} updated")
            return True
        except Exception as e:
            self.connection.rollback()
            logger.error(f"DB UPDATE error for {table}: {str(e)}")
            return False

    def close(self):
        if self.connection:
            self.connection.close()


def _read_env():
    env_file = Path(__file__).parent.parent / '.env'
    config = {}
    if env_file.exists():
        with open(env_file, 'r') as f:
            for line in f:
                if '=' in line and not line.startswith('#'):
                    key, value = line.strip().split('=', 1)
                    config[key.strip()] = value.strip().strip('"').strip("'")
    return config


def get_document_file(doc, db_config):
    stored_filename = doc['stored_filename']
    storage_path = doc.get('storage_path', '')
    doc_id = doc.get('id', '?')
    logger.info(f"Locate file for document {doc_id}: {stored_filename}")
    base_paths = [
        Path(__file__).parent.parent / 'storage' / 'app' / 'public' / 'documents' / stored_filename,
        Path(__file__).parent.parent / 'storage' / 'app' / 'documents' / stored_filename,
        Path(__file__).parent.parent / 'storage' / 'app' / 'public' / 'submitted_documents' / stored_filename,
        Path(__file__).parent.parent / 'storage' / 'app' / 'submitted_documents' / stored_filename,
        Path(__file__).parent.parent / 'storage' / 'app' / storage_path,
        Path(__file__).parent.parent / 'storage' / 'uploads' / stored_filename,
    ]
    for local_path in base_paths:
        if local_path.exists():
            logger.info(f"File found locally: {local_path}")
            return local_path, False

    logger.info("File not found locally, trying R2")
    
    # First try to read R2 config from .env (for local tests)
    config = _read_env()
    r2_key = config.get('R2_ACCESS_KEY_ID', '')
    r2_secret = config.get('R2_SECRET_ACCESS_KEY', '')
    r2_bucket = config.get('R2_BUCKET', '')
    r2_endpoint = config.get('R2_ENDPOINT', '')
    
    # If not found in .env, read from database Utility settings
    if not (r2_key and r2_secret and r2_bucket and r2_endpoint):
        logger.info("R2 config not in .env, trying database")
        try:
            if mysql_connector:
                db_conn = mysql_connector.connect(**db_config)
                cursor = db_conn.cursor(dictionary=True)
                # Query settings table which stores settings as name-value pairs
                cursor.execute("SELECT `name`, `value` FROM settings WHERE `name` IN ('storage_setting', 'r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint')")
                settings = {row['name']: row['value'] for row in cursor.fetchall()}
                db_conn.close()
                
                storage_setting = settings.get('storage_setting', 'local')
                if storage_setting == 'r2':
                    r2_key = settings.get('r2_key', '')
                    r2_secret = settings.get('r2_secret', '')
                    r2_bucket = settings.get('r2_bucket', '')
                    r2_endpoint = settings.get('r2_endpoint', '')
                    
                    if r2_key and r2_endpoint:
                        logger.info(f"✅ R2 config loaded from DB: bucket={r2_bucket}, endpoint={r2_endpoint[:40]}...")
                    else:
                        logger.warning("R2 config incomplete in DB")
                else:
                    logger.info(f"Storage setting is '{storage_setting}', not R2")
        except Exception as e:
            logger.warning(f"Could not read R2 config from DB: {str(e)}")
    
    if r2_key and r2_secret and r2_bucket and r2_endpoint:
        try:
            import boto3
            from botocore.config import Config
            if not storage_path:
                storage_path = f'documents/{stored_filename}'
            
            logger.info(f"Downloading from R2: bucket={r2_bucket}, key={storage_path}")
            logger.info(f"R2 endpoint: {r2_endpoint}")
            
            # Create boto3 client with proper config
            s3_config = Config(
                retries={'max_attempts': 3, 'mode': 'standard'},
                connect_timeout=10,
                read_timeout=30,
            )
            
            s3 = boto3.client(
                's3',
                endpoint_url=r2_endpoint,
                aws_access_key_id=r2_key,
                aws_secret_access_key=r2_secret,
                region_name='auto',
                config=s3_config
            )
            
            # Test bucket access
            try:
                s3.head_bucket(Bucket=r2_bucket)
                logger.info(f"✅ R2 bucket '{r2_bucket}' is accessible")
            except Exception as e:
                logger.error(f"❌ Cannot access R2 bucket '{r2_bucket}': {str(e)}")
                raise
            
            temp_dir = Path(__file__).parent.parent / 'storage' / 'temp'
            temp_dir.mkdir(parents=True, exist_ok=True)
            temp_path = temp_dir / stored_filename
            
            logger.info(f"Downloading file from R2 ({r2_bucket}/{storage_path}) to {temp_path}...")
            s3.download_file(r2_bucket, storage_path, str(temp_path))
            file_size = temp_path.stat().st_size
            logger.info(f"✅ File downloaded from R2: {temp_path} ({file_size} bytes)")
            return temp_path, True
        except ImportError as e:
            logger.error(f'❌ boto3 not installed: {str(e)}. Install with: pip install boto3')
        except Exception as e:
            logger.error(f"❌ R2 download error: {type(e).__name__}: {str(e)}")
            import traceback
            logger.error(f"Traceback:\n{traceback.format_exc()}")
    else:
        missing = []
        if not r2_key:
            missing.append('R2_ACCESS_KEY_ID')
        if not r2_secret:
            missing.append('R2_SECRET_ACCESS_KEY')
        if not r2_bucket:
            missing.append('R2_BUCKET')
        if not r2_endpoint:
            missing.append('R2_ENDPOINT')
        logger.error(f"❌ R2 credentials incomplete. Missing: {', '.join(missing)}")
    
    logger.error(f"❌ File not found (document {doc_id}): {stored_filename}")
    return None, False


def get_document_file_generic(doc, db_config, source):
    """
    Generic document file getter - works with local storage or R2.
    Retrieves R2 credentials from database (settings table), not .env
    """
    if source == 'submitted':
        return get_document_file(doc, db_config)
    
    file_path_db = doc.get('file_path', '') or ''
    file_name = doc.get('file_name', '') or Path(file_path_db).name
    
    logger.info(f"🔍 Searching for file: {file_name} (path: {file_path_db})")
    
    # Try local paths first
    base_paths = [
        Path(__file__).parent.parent / 'storage' / 'app' / 'public' / file_path_db,
        Path(__file__).parent.parent / 'storage' / 'app' / file_path_db,
        Path(__file__).parent.parent / 'storage' / 'uploads' / file_name,
    ]
    logger.info(f"Checking {len(base_paths)} local paths...")
    for local_path in base_paths:
        if local_path.exists():
            logger.info(f"✅ File found locally: {local_path}")
            return local_path, False
    
    logger.info("File not found locally, trying R2...")
    
    # Try R2 download - get credentials from database settings table
    try:
        if not mysql_connector:
            logger.error('mysql.connector not available for R2 credentials fetch')
            logger.error(f"File not found (source={source}, id={doc.get('id')})")
            return None, False
            
        logger.info("Connecting to database for R2 credentials...")
        connection = mysql_connector.connect(**db_config)
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT `name`, `value` FROM settings WHERE `name` IN ('storage_setting', 'r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint')")
        settings = {row['name']: row['value'] for row in cursor.fetchall()}
        connection.close()
        
        logger.info(f"✅ R2 config loaded from DB (storage_setting={settings.get('storage_setting')})")
        
        storage_setting = settings.get('storage_setting', 'local')
        
        if storage_setting == 'r2':
            r2_key = settings.get('r2_key', '')
            r2_secret = settings.get('r2_secret', '')
            r2_bucket = settings.get('r2_bucket', '')
            r2_endpoint = settings.get('r2_endpoint', '')
            
            logger.info(f"Storage=R2, bucket={r2_bucket}, endpoint={r2_endpoint[:50]}...")
            
            if r2_key and r2_secret and r2_bucket and r2_endpoint:
                try:
                    import boto3
                    storage_path = file_path_db or file_name
                    r2_endpoint_clean = r2_endpoint.replace('https://', '').replace('http://', '')
                    
                    logger.info(f"Creating S3 client for R2...")
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
                    logger.info(f"✅ File downloaded from R2: {temp_path}")
                    return temp_path, True
                except ImportError:
                    logger.error('boto3 not installed for R2 download')
                except Exception as e:
                    logger.error(f"R2 download error: {type(e).__name__}: {str(e)}")
            else:
                logger.error(f"R2 credentials incomplete in database")
        else:
            logger.info(f"Storage setting is '{storage_setting}', not R2")
    except Exception as e:
        logger.error(f"Database R2 credentials fetch error: {type(e).__name__}: {str(e)}")
    
    logger.error(f"File not found (source={source}, id={doc.get('id')}, path={file_path_db})")
    return None, False


def fetch_documents(db, source, document_id=None, limit=50):
    cursor = db.connection.cursor(dictionary=True)
    if source == 'submitted':
        if document_id:
            cursor.execute(
                """
                SELECT id, original_filename, stored_filename, storage_path
                FROM submitted_documents
                WHERE id = %s
                """,
                (document_id,),
            )
        else:
            cursor.execute(
                """
                SELECT id, original_filename, stored_filename, storage_path
                FROM submitted_documents
                WHERE processing_status = 'pending'
                LIMIT %s
                """,
                (limit,),
            )
        return cursor.fetchall()
    if source == 'legal':
        if document_id:
            cursor.execute(
                """
                SELECT id, file_path, file_name, file_size
                FROM legal_documents
                WHERE id = %s
                """,
                (document_id,),
            )
        else:
            cursor.execute(
                """
                SELECT id, file_path, file_name, file_size
                FROM legal_documents
                WHERE (extracted_text IS NULL OR extracted_text = '')
                LIMIT %s
                """,
                (limit,),
            )
        return cursor.fetchall()
    if source == 'template':
        if document_id:
            cursor.execute(
                """
                SELECT id, file_path, file_name, file_size
                FROM document_templates
                WHERE id = %s
                """,
                (document_id,),
            )
        else:
            cursor.execute(
                """
                SELECT id, file_path, file_name, file_size
                FROM document_templates
                WHERE (extracted_text IS NULL OR extracted_text = '')
                LIMIT %s
                """,
                (limit,),
            )
        return cursor.fetchall()
    if source == 'fiscal':
        if document_id:
            cursor.execute(
                """
                SELECT id, file_path, file_name, file_size
                FROM fiscal_social_resources
                WHERE id = %s
                """,
                (document_id,),
            )
        else:
            cursor.execute(
                """
                SELECT id, file_path, file_name, file_size
                FROM fiscal_social_resources
                WHERE (extracted_text IS NULL OR extracted_text = '')
                LIMIT %s
                """,
                (limit,),
            )
        return cursor.fetchall()
    raise ValueError(f"Unknown source: {source}")


def main():
    parser = argparse.ArgumentParser(description='Extract text from documents')
    parser.add_argument('--document-id', type=int, help='Document ID to process (optional)')
    parser.add_argument('--source', choices=['submitted', 'legal', 'template', 'fiscal'], default='submitted')
    parser.add_argument('--limit', type=int, default=50, help='Max batch size')
    parser.add_argument('--max-size-mb', type=int, default=15, help='Skip files larger than this (MB)')
    parser.add_argument('--ignore-size', action='store_true', help='Do not skip large files based on size')
    parser.add_argument('--ocr-pages', type=str, default='10', help='Max pages to OCR; use "all" for entire document, or a number')
    parser.add_argument('--ocr-lang', type=str, default='fra+eng', help='Tesseract language(s), e.g., fra, eng, or fra+eng')
    parser.add_argument('--ocr-dpi', type=int, default=150, help='Rendering DPI for OCR images (higher may improve OCR but uses more memory)')
    parser.add_argument('--ocr-delay', type=float, default=0.0, help='Seconds to wait between OCR pages (prevents memory buildup; try 0.5-2.0 for huge files)')
    parser.add_argument('--force-ocr', action='store_true', help='Run OCR even when a page has extractable text')
    args = parser.parse_args()

    logger.info(f"Start document processing (source={args.source}, max_size={args.max_size_mb}MB)")

    db = DatabaseUpdater()
    if not db.connect():
        logger.error('DB connection failed')
        return False

    try:
        documents = fetch_documents(db, args.source, args.document_id, args.limit)
    except Exception as e:
        logger.error(f"Fetch documents error: {str(e)}")
        db.close()
        return False

    if not documents:
        logger.info('No documents to process')
        db.close()
        return True

    logger.info(f"{len(documents)} document(s) to process")

    for doc in documents:
        doc_id = doc['id']
        filename = doc.get('original_filename') or doc.get('file_name') or 'document'
        logger.info(f"Processing: {filename} (ID: {doc_id})")

        file_path, is_temp = get_document_file_generic(doc, db.db_config, args.source)
        if not file_path:
            logger.warning(f"File not found for {filename}")
            if args.source == 'submitted':
                db.mark_failed(doc_id, f"File not found: {filename}")
            continue

        # Check file size before processing
        try:
            file_size_mb = file_path.stat().st_size / (1024 * 1024)
            if not args.ignore_size and not args.document_id and file_size_mb > args.max_size_mb:
                logger.warning(f"Skipping {filename} ({file_size_mb:.1f}MB > {args.max_size_mb}MB limit). Use --max-size-mb to increase, --ignore-size to bypass, or process individually with --document-id {doc_id}")
                if args.source == 'submitted':
                    db.mark_failed(doc_id, f"File too large for batch processing: {file_size_mb:.1f}MB")
                if is_temp and file_path.exists():
                    try:
                        os.unlink(file_path)
                    except Exception:
                        pass
                continue
        except Exception as e:
            logger.warning(f"Could not check file size: {e}")

        try:
            # Configure OCR flags
            ocr_pages = args.ocr_pages
            if isinstance(ocr_pages, str):
                ocr_pages_val = -1 if ocr_pages.lower() == 'all' else int(ocr_pages)
            else:
                ocr_pages_val = int(ocr_pages)
            extractor = DocumentExtractor(
                file_path,
                ocr_max_pages=ocr_pages_val,
                ocr_lang=args.ocr_lang,
                ocr_dpi=args.ocr_dpi,
                force_ocr=args.force_ocr,
                ocr_page_delay=args.ocr_delay,
            )
            extracted_text, error = extractor.extract()
            if error:
                logger.error(f"Extraction error: {error}")
                if args.source == 'submitted':
                    db.mark_failed(doc_id, error)
                continue
            if not extracted_text or extracted_text.strip() == '':
                logger.warning(f"No text extracted from {filename}")
                if args.source == 'submitted':
                    db.mark_failed(doc_id, 'No text found in document')
                continue

            if args.source == 'submitted':
                success, db_error = db.update_document(
                    doc_id,
                    extracted_text,
                    metadata={
                        'extracted_at': datetime.now().isoformat(),
                        'file_type': file_path.suffix,
                        'filename': filename,
                        'file_size': file_path.stat().st_size,
                    }
                )
                if not success:
                    db.mark_failed(doc_id, db_error)
            else:
                db.update_library_document(args.source, doc_id, extracted_text)
        except Exception as e:
            logger.error(f"Unexpected error processing {filename}: {type(e).__name__}: {str(e)}")
            if args.source == 'submitted':
                db.mark_failed(doc_id, f"Processing error: {str(e)}")
        finally:
            if is_temp and file_path and Path(file_path).exists():
                try:
                    os.unlink(file_path)
                    logger.info(f"Temp file removed: {file_path}")
                except Exception:
                    pass

    db.close()
    logger.info('Processing finished')
    return True


if __name__ == '__main__':
    success = main()
    sys.exit(0 if success else 1)
