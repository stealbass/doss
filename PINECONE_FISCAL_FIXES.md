# Infrastructure Fixes - Pinecone & Fiscal

**Date:** January 21, 2026, 15:30
**Status:** ✅ Code fixes applied, ⏳ Needs deployment

## Issues Fixed

### 1. ✅ Pinecone DNS Resolution (cURL error 6)

**Problem:**
```
cURL error 6: Could not resolve host: dossy-legal-docs.svc.aped-4627.pinecone.io
```

**Root Cause:**
- Code used generic format: `{index}.svc.{env}.pinecone.io`
- Actual Pinecone host: `dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io`
- Missing suffixes: `-7udtg21` (index) and `-b74a` (environment)

**Fix Applied:**
Added to `.env`:
```dotenv
PINECONE_HOST=dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io
```

**Verification:**
- File: `.env` (line 48)
- From Pinecone Dashboard: https://dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io

---

### 2. ✅ Fiscal Resources Column Name (SQLSTATE[42S22])

**Problem:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'name' in 'WHERE'
```

**Root Cause:**
- `fiscal_social_resources` table uses `title` column (like `legal_documents`)
- Code incorrectly used `name` column (from `document_templates`)

**Fix Applied:**
Changed in `app/Services/AdvancedRagService.php` (lines 1130-1180):
```php
// ❌ BEFORE (6 occurrences):
$q->orWhere('name', 'LIKE', "%{$keyword}%")

// ✅ AFTER:
$q->orWhere('title', 'LIKE', "%{$keyword}%")
```

**Schema Verified:**
From `app/Models/FiscalSocialResource.php`:
```php
protected $fillable = [
    'category_id',
    'title',  // ← Correct column
    'slug',
    'description',
    'country',
    'year',
    ...
];
```

---

### 3. ⏳ Pinecone Index Empty (0 records)

**Problem:**
- Pinecone index shows: "No records yet"
- Record count: 0
- All queries fallback to database

**Root Cause:**
- Documents never indexed after Pinecone reconfiguration
- ProcessDocumentForRAG job not executed for existing docs

**Fix Required:**
Reindex all completed documents:

**Option A - Artisan Command (if exists):**
```bash
php artisan rag:reindex-all
```

**Option B - Queue All Documents:**
```php
// Via Tinker or controller route:
use App\Models\SubmittedDocument;
use App\Jobs\ProcessDocumentForRAG;

$documents = SubmittedDocument::where('processing_status', 'completed')
    ->whereNotNull('extracted_text')
    ->get();

foreach ($documents as $doc) {
    ProcessDocumentForRAG::dispatch($doc);
}

echo "Dispatched {$documents->count()} documents for reindexing\n";
```

**Option C - Re-upload via UI:**
- Manual re-upload of documents (slow, not recommended)

---

## Deployment Steps

### On Alwaysdata (Production Server):

1. **Upload Fixed Files:**
   ```bash
   # Upload via SFTP or git pull:
   - .env (with PINECONE_HOST)
   - app/Services/AdvancedRagService.php (with 'title' column)
   ```

2. **Clear Laravel Caches:**
   ```bash
   cd ~/www/threesixty
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Test Pinecone Connection:**
   ```bash
   # Check logs for DNS resolution success:
   tail -f storage/logs/laravel.log | grep -i pinecone
   ```
   Expected: No more "cURL error 6" messages

4. **Test Fiscal Query:**
   Ask via chat: "Quels sont les taux de TVA au Cameroun?"
   Expected: No "column not found" errors in logs

5. **Reindex Documents (Choose One):**

   **Option A - Via Artisan:**
   ```bash
   php artisan rag:reindex-all
   ```

   **Option B - Via Tinker:**
   ```bash
   php artisan tinker
   
   use App\Models\SubmittedDocument;
   use App\Jobs\ProcessDocumentForRAG;
   
   $docs = SubmittedDocument::where('processing_status', 'completed')
       ->whereNotNull('extracted_text')
       ->get();
   
   foreach ($docs as $doc) {
       ProcessDocumentForRAG::dispatch($doc);
   }
   
   echo "Dispatched {$docs->count()} documents";
   ```

   **Option C - Via Queue Worker:**
   ```bash
   # Start queue worker to process jobs:
   php artisan queue:work --queue=rag,default --tries=3 --timeout=300
   ```

6. **Monitor Reindexing Progress:**
   ```bash
   # Check job progress:
   php artisan queue:monitor rag,default
   
   # Or check Pinecone dashboard:
   # https://app.pinecone.io/ → dossy-legal-docs index
   # Should see record count increasing
   ```

---

## Verification Tests

### 1. Pinecone DNS Resolution
```bash
# Test query via chat:
"Quelles sont les obligations fiscales d'une SARL?"

# Expected in logs:
✅ "Pinecone query successful"
✅ No "cURL error 6"
```

### 2. Fiscal Column Fix
```bash
# Test query via chat:
"Quels sont les taux de TVA au Cameroun?"

# Expected in logs:
✅ No "Unknown column 'name'" error
✅ fiscal_social_resources results returned
```

### 3. Pinecone Index Populated
```bash
# Check Pinecone dashboard:
✅ Record count > 0 (should match completed documents count)
✅ Index status: "Ready"

# Test query via chat:
"Quels sont les droits des travailleurs en cas de licenciement?"

# Expected:
✅ Results from Pinecone (not fallback)
✅ No "Pinecone returned no results" warning
```

### 4. Source Relevance
```bash
# Test different question types:

Labor: "Quels sont les droits des travailleurs en cas de licenciement?"
Expected: Code du Travail, NOT foncier/notaires

Fiscal: "Quels sont les taux de TVA au Cameroun?"
Expected: Fiscal resources, NOT labor docs

Land: "Comment enregistrer un titre foncier?"
Expected: Décret foncier, NOT labor/fiscal
```

---

## Expected Results

### Before Fixes:
- ❌ All Pinecone queries: cURL error 6
- ❌ Fiscal queries: Column not found error
- ❌ RAG: 100% database fallback
- ❌ Same sources for all questions
- ❌ Labor questions → foncier docs

### After Fixes:
- ✅ Pinecone DNS resolves correctly
- ✅ Fiscal queries work without errors
- ✅ RAG uses Pinecone (after reindex)
- ✅ Different sources for different questions
- ✅ Labor questions → labor docs only

---

## Files Modified

1. **`.env`** (line 48)
   - Added: `PINECONE_HOST=dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io`

2. **`app/Services/AdvancedRagService.php`** (lines 1136-1165)
   - Changed: 6 occurrences of `orWhere('name'` → `orWhere('title'`
   - Locations: fiscal_social_resources search queries

---

## Critical Notes

⚠️ **Cache Clear Required:**
Laravel caches `.env` config. Must run `php artisan config:clear` after updating `.env`.

⚠️ **Reindexing Time:**
- Indexing 1000 documents ≈ 30-60 minutes
- Uses OpenAI API (text-embedding-3-small)
- Requires queue worker running

⚠️ **Queue Worker:**
If queue worker not running:
```bash
php artisan queue:work --queue=rag,default --tries=3 --daemon
```

⚠️ **OpenAI API Limits:**
- Rate limit: 3000 RPM
- Batch size: 10 docs/minute safe
- Monitor: OpenAI dashboard for usage

---

## Support Commands

```bash
# Check Pinecone config:
php artisan tinker
config('services.pinecone')

# Test Pinecone connection:
$client = new \GuzzleHttp\Client();
$response = $client->get('https://dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io/vectors/fetch?ids=test');
echo $response->getStatusCode();

# Count completed documents:
php artisan tinker
App\Models\SubmittedDocument::where('processing_status', 'completed')->count();

# Check queue jobs:
php artisan queue:monitor
php artisan queue:failed

# Retry failed jobs:
php artisan queue:retry all
```

---

## Next Steps

1. Deploy fixes to production (upload files)
2. Clear Laravel caches
3. Test Pinecone connection (should succeed)
4. Test fiscal queries (should work)
5. Reindex all documents (30-60 min)
6. Verify source relevance with test questions

---

**Contact:** Support team
**Documentation:** [Pinecone Dashboard](https://app.pinecone.io/)
**Logs:** `storage/logs/laravel.log`
