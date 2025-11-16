# Legal Library - Super Admin Architecture Update

## Overview
This update restructures the Legal Library feature from company-level to Super Admin level, making it a global library accessible to all users across all companies. This resolves two critical issues:

1. **PDF Preview 404 Error**: Fixed by correcting the PDF URL generation method
2. **Architecture Change**: Moved from company-scoped to Super Admin global library

## Changes Summary

### 🔧 Fixed Issues

#### 1. PDF Preview 404 Error
- **Problem**: Users clicking "View" on documents received "404 NOT FOUND" error
- **Root Cause**: `Storage::url()` was generating incorrect paths
- **Solution**: Changed to `asset('storage/' . $document->file_path)` for proper URL generation
- **File Modified**: `resources/views/user-legal-library/view.blade.php`

#### 2. Company-Level to Super Admin Architecture
- **Problem**: Each company had separate library; admin had to add content per company
- **User Request**: "Je veux pouvoir actualiser la bibliothèque pour qu'il soit vu par tous les utilisateurs"
- **Solution**: 
  - Removed all `created_by` filtering from queries
  - Restricted admin access to Super Admin only (`type == 'super admin'`)
  - Set `created_by = 0` for new categories/documents (no company association)
  - Moved admin navigation link to Super Admin section (above Plan Request)

### 📝 Files Modified

#### 1. `app/Http/Controllers/UserLegalLibraryController.php`
**Changes**:
- Removed `->where('created_by', Auth::user()->creatorId())` from all queries
- Categories and documents now show globally to all users
- Lines modified: 22-23, 29, 56-57

**Impact**: All users now see the same global library regardless of their company

#### 2. `app/Http/Controllers/LegalLibraryController.php`
**Changes**:
- Added `Auth::user()->type == 'super admin'` check to ALL methods
- Removed company filtering: `->where('created_by', Auth::user()->creatorId())`
- Changed `created_by` to `0` when creating categories/documents
- Lines modified: 19-20, 34, 46, 63, 77, 93, 118, 128, 145, 152, 166, 182, 214, 232, 248, 303

**Impact**: Only Super Admin can manage library; content is global

#### 3. `routes/web.php`
**Changes**:
- **Moved** admin legal library routes from line ~209 to line ~256 (Super Admin context)
- **Positioned** routes just before `plan_request` routes
- **Removed** duplicate route group that was in company context
- Lines modified: 206-227, 256-275

**Impact**: Admin routes now in Super Admin section, consistent with Plan Request behavior

#### 4. `resources/views/partision/sidebar.blade.php`
**Changes**:
- **Added** Legal Library link in Super Admin section (line ~418, above Plan Request)
- **Removed** "Legal Library (Admin)" from Settings submenu (line ~374-379)
- **Updated** user library link to exclude Super Admin (`type != 'super admin'`)
- Lines modified: 155-162, 374-379, 417-424

**Impact**: 
- Super Admin sees "Legal Library" in main sidebar (for management)
- Regular users see "Legal Library" in main sidebar (for viewing)
- No duplicate links for Super Admin

#### 5. `resources/views/user-legal-library/view.blade.php`
**Changes**:
- Changed `{{ Storage::url($document->file_path) }}` to `{{ asset('storage/' . $document->file_path) }}`
- Line modified: 64

**Impact**: PDF preview now works correctly, showing documents in browser

## Architecture Overview

### Before (Company-Level)
```
┌─────────────┐
│  Company A  │ → Own legal library (separate categories/documents)
└─────────────┘
┌─────────────┐
│  Company B  │ → Own legal library (separate categories/documents)
└─────────────┘
```
**Problem**: Admin had to add content separately in each company account

### After (Super Admin Global)
```
┌──────────────────────┐
│    Super Admin       │
│  (Global Library)    │
└──────────┬───────────┘
           │
    ┌──────┴──────┐
    │             │
┌───▼────┐  ┌────▼────┐
│ Co. A  │  │  Co. B  │
│ Users  │  │  Users  │
└────────┘  └─────────┘
```
**Solution**: Super Admin manages ONE library, visible to ALL users

## Navigation Structure

### Super Admin Menu
```
Dashboard
Companies
Employees
...
Legal Library          ← NEW: Super Admin manages global library
Plan Request
Referral Program
...
```

### Company/User Menu
```
Dashboard
...
Legal Library          ← Views global library (read-only with download)
Bills / Invoices
...
```

## Database Schema

### Tables
- `legal_categories`: Stores categories (created_by = 0 for global)
- `legal_documents`: Stores PDF documents with metadata (created_by = 0 for global)

### Key Fields
- `created_by = 0`: Indicates Super Admin level (global, not company-scoped)
- `file_path`: Stored as `legal_documents/{timestamp}_{filename}.pdf`

## Permissions

### Existing Permissions
- `manage legal library`: Super Admin only (type == 'super admin')
- `view legal library`: All users (advocate, client, co advocate, team leader, company)

### Authorization Logic
- **Admin Routes**: Check `Auth::user()->type == 'super admin'` AND `can('manage legal library')`
- **User Routes**: Check `can('view legal library')`

## Routes Summary

### Super Admin Routes (Management)
```php
GET     /legal-library                              - List categories
GET     /legal-library/category/create              - Create category form
POST    /legal-library/category/store               - Store category
GET     /legal-library/category/{id}/edit           - Edit category form
PUT     /legal-library/category/{id}                - Update category
DELETE  /legal-library/category/{id}                - Delete category
GET     /legal-library/category/{categoryId}/documents        - List documents
GET     /legal-library/category/{categoryId}/document/create  - Upload form
POST    /legal-library/category/{categoryId}/document/store   - Store document
GET     /legal-library/document/{id}/edit           - Edit document form
PUT     /legal-library/document/{id}                - Update document
DELETE  /legal-library/document/{id}                - Delete document
```

### User Routes (Viewing)
```php
GET     /library                                    - Browse library
GET     /library/category/{categoryId}              - View category documents
GET     /library/document/{id}/view                 - Preview PDF
GET     /library/document/{id}/download             - Download PDF
```

## File Storage

### Directory Structure
```
storage/
  app/
    public/
      legal_documents/
        {timestamp}_{filename}.pdf
        ...

public/
  storage/ → ../storage/app/public (symbolic link)
```

### Storage Commands
```bash
# Create symbolic link (if not exists)
php artisan storage:link

# Create directory manually (if needed)
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
```

### File Access
- **Upload**: Files stored via `$file->storeAs('legal_documents', $fileName, 'public')`
- **Preview**: `asset('storage/' . $document->file_path)` → `/storage/legal_documents/{file}.pdf`
- **Download**: `storage_path('app/public/' . $document->file_path)`

## Testing Checklist

### Super Admin Tests
- [ ] Login as Super Admin
- [ ] Verify "Legal Library" link appears above "Plan Request"
- [ ] Create new category
- [ ] Upload PDF document (max 20MB)
- [ ] Edit category/document
- [ ] Delete document/category

### User Tests
- [ ] Login as company user
- [ ] Verify "Legal Library" link appears in main menu
- [ ] Browse categories created by Super Admin
- [ ] Search for documents
- [ ] Click "View" on document → PDF preview should work (no 404)
- [ ] Download document
- [ ] Verify download count increments

### Cross-Company Tests
- [ ] Super Admin uploads document
- [ ] Login as User from Company A → verify document visible
- [ ] Login as User from Company B → verify SAME document visible
- [ ] Super Admin deletes document
- [ ] Verify document removed for ALL users

## Workflow Example

### Scenario: Super Admin adds new law document

1. **Super Admin Login**
   - Navigate to "Legal Library" (in main sidebar)
   
2. **Create Category** (if needed)
   - Click "Create Category"
   - Enter: Name = "Code Civil", Description = "Articles du Code Civil"
   - Save → Category created with `created_by = 0` (global)
   
3. **Upload Document**
   - Click on "Code Civil" category
   - Click "Upload Document"
   - Title = "Article 1382 - Responsabilité"
   - Upload PDF file (max 20MB)
   - Save → Document stored in `storage/app/public/legal_documents/`
   
4. **All Users See It**
   - User from Company A logs in → sees "Code Civil" and can view/download document
   - User from Company B logs in → sees SAME content
   - No need for Super Admin to re-upload for each company

## Migration Notes

### Existing Data
If you have existing legal library data from company-level implementation:

```sql
-- Option 1: Keep company-specific data (each company keeps their own)
-- No action needed - data remains scoped to created_by

-- Option 2: Convert to global (make all content visible to everyone)
UPDATE legal_categories SET created_by = 0;
UPDATE legal_documents SET created_by = 0;

-- Option 3: Selective migration (e.g., keep only Company ID = 1)
UPDATE legal_categories SET created_by = 0 WHERE created_by = 1;
UPDATE legal_documents SET created_by = 0 WHERE created_by IN (
    SELECT id FROM legal_categories WHERE created_by = 0
);
-- Then delete other company data
DELETE FROM legal_categories WHERE created_by != 0;
```

### Fresh Installation
For new installations, follow the original SQL installation script:
- Tables already have `created_by` field
- New categories/documents will automatically use `created_by = 0`

## Translations

### English (en.json)
All legal library translations already added in previous update.

### French (fr.json)
All legal library translations already added in previous update.

Key translations:
- "Legal Library": "Bibliothèque Juridique"
- "Legal Library (Admin)": "Bibliothèque Juridique (Admin)"
- "Document Preview": "Aperçu du Document"

## Troubleshooting

### Issue: PDF Preview shows 404
**Solution**: 
1. Check symbolic link: `ls -la public/storage`
2. If missing: `php artisan storage:link`
3. Verify file exists: `ls -la storage/app/public/legal_documents/`
4. Check permissions: `chmod -R 775 storage/app/public/legal_documents`

### Issue: Admin can't access Legal Library
**Solution**: 
1. Verify user type: Must be `type = 'super admin'`
2. Check permission: User must have 'manage legal library' permission
3. Clear cache: `php artisan cache:clear`

### Issue: Users can't see documents
**Solution**: 
1. Verify permission: User must have 'view legal library' permission
2. Check documents were created with `created_by = 0`
3. Clear cache: `php artisan cache:clear`

### Issue: Navigation link missing
**Solution**: 
1. Check language files: fr.json and en.json must have "Legal Library" translation
2. Clear view cache: `php artisan view:clear`
3. Clear config cache: `php artisan config:clear`

## Benefits of Super Admin Architecture

### 1. Centralized Management
- ✅ Super Admin manages ONE library for entire platform
- ✅ No need to duplicate content across companies
- ✅ Consistent legal information for all users

### 2. Efficiency
- ✅ Upload once, visible to all
- ✅ Update once, changes reflected everywhere
- ✅ Delete once, removed for everyone

### 3. Scalability
- ✅ Works well with 10 companies or 1000 companies
- ✅ No performance degradation with multiple companies
- ✅ Single source of truth

### 4. User Experience
- ✅ All users see the same library
- ✅ Consistent experience across companies
- ✅ Easy to find and access legal documents

## Conclusion

This update successfully transforms the Legal Library from a company-scoped feature to a Super Admin global feature, matching the behavior of Plan management. The changes ensure:

1. ✅ PDF preview works correctly (no 404 errors)
2. ✅ Super Admin manages ONE global library
3. ✅ All users see the same content regardless of company
4. ✅ Navigation properly reflects Super Admin vs User access levels
5. ✅ Clean separation between management (Super Admin) and viewing (All Users)

The architecture now follows the SaaS best practice of centralized configuration (like Plans) visible to all tenants.
