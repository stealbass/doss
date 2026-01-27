# SuperAdmin Content Manager Role - Testing Guide

## Quick Test Flow

### Test 1: Create Content Manager Employee

**Steps:**
1. Login to SuperAdmin dashboard as super admin
2. Navigate to: Dashboard → Employees → Create Employee
3. Fill in form:
   - Name: `Test Content Manager`
   - Email: `content_manager@test.com`
   - Password: `SecurePassword123`
4. **Check only these permissions:**
   - ☑ Manage legal-library
   - ☑ Manage document-template
   - ☑ Manage fiscal-resources
   - ☐ Manage crm (leave unchecked)
   - ☐ Manage support ticket (leave unchecked)
5. Click **Create**
6. Verify success message appears

**Expected Result:**
- Employee created successfully
- Database should show `permission_json` containing IDs 3, 4, 5

### Test 2: Verify Content Manager Access

**Steps:**
1. Logout from SuperAdmin
2. Login as `content_manager@test.com` / `SecurePassword123`
3. Check dashboard - should see:
   - ✓ Legal Library module accessible
   - ✓ Document Templates module accessible  
   - ✓ Fiscal Resources module accessible
   - ✗ CRM module NOT visible (or shows permission denied if accessed)
   - ✗ Support Tickets module NOT visible (or shows permission denied if accessed)

**Try each module:**
- Click on **Legal Library** → Should load normally
- Click on **Document Templates** → Should load normally
- Click on **Fiscal Resources** → Should load normally
- If available, try **CRM** → Should show "Permission Denied"
- If available, try **Support Tickets** → Should show "Permission Denied"

### Test 3: Create Limited Permission Employee

**Steps:**
1. Logout and login as super admin
2. Create another employee but check ONLY:
   - ☑ Manage legal-library (only one)
   - ☐ Manage document-template
   - ☐ Manage fiscal-resources
3. Click **Create**
4. Logout and login as this new employee
5. Try to access each module:
   - Legal Library → ✓ Should work
   - Document Templates → ✗ Should show "Permission Denied"
   - Fiscal Resources → ✗ Should show "Permission Denied"

### Test 4: Edit Existing Employee Permissions

**Steps:**
1. Logout and login as super admin
2. Navigate to: Employees → Find "Test Content Manager"
3. Click **Edit**
4. Verify checkboxes show current permissions:
   - ☑ Manage legal-library (checked)
   - ☑ Manage document-template (checked)
   - ☑ Manage fiscal-resources (checked)
5. **Remove one permission:** Uncheck "Manage fiscal-resources"
6. Click **Update**
7. Logout and login as that employee again
8. Verify Fiscal Resources now shows "Permission Denied"

### Test 5: Database Verification

**Using Laravel Tinker:**

```bash
php artisan tinker
```

```php
// Check employee permissions
$employee = App\Models\User::where('email', 'content_manager@test.com')->first();
dd(json_decode($employee->permission_json, true));

// Expected output:
// array:3 [
//   "3" => "manage legal-library"
//   "4" => "manage document-template"  
//   "5" => "manage fiscal-resources"
// ]

// Check permission checking logic
$permissions = json_decode($employee->permission_json, true);
dd(in_array('manage legal-library', $permissions));  // Should return true
dd(in_array(3, $permissions));                       // Should return true
dd(in_array('manage crm', $permissions));            // Should return false
dd(in_array(1, $permissions));                       // Should return false
```

## Troubleshooting

### Issue: Checkboxes show all permissions but only 2 modules display

**Solution:**
- Views were not updated properly
- Clear browser cache: `Ctrl+Shift+Del` → Clear all
- Restart Laravel: `php artisan cache:clear`
- Check that `create.blade.php` and `edit.blade.php` have dynamic `$modules` derivation

### Issue: Employee created but permissions not saved

**Solution:**
- Check `$request->permissions` is being submitted in form POST
- Verify form has `name="permissions[]"` on checkboxes
- Check EmployeeController.store() receives the values

### Issue: Permission check always returns false

**Solution:**
- Verify user type is `'superAdminEmployee'` (check exact string)
- Verify `permission_json` column exists in users table
- Verify JSON is valid: Use Tinker to check `json_decode($user->permission_json, true)`
- Verify permission ID/name matches exactly (case-sensitive)

### Issue: Access denied but should have permission

**Solution:**
- Clear app cache: `php artisan cache:clear`
- Check Tinker that permissions are stored correctly
- Verify helper method is being called (not old code)
- Check controller file was actually updated

## Files to Check for Debugging

### Permission Storage
- File: `app/Http/Controllers/EmployeeController.php`
- Method: `store()` at line ~100
- Check: `$user['permission_json'] = json_encode($permission_arr);`

### Permission Checking
- File: `app/Http/Controllers/LegalLibraryController.php`
- Method: `canManageLegalLibrary()` at line 15
- File: `app/Http/Controllers/DocumentTemplateController.php`
- Method: `canManageDocumentTemplate()` at line 17
- File: `app/Http/Controllers/FiscalSocialResourceController.php`
- Method: `canManageFiscalResources()` at line 15

### View Checkboxes
- File: `resources/views/employee/create.blade.php`
- Check: `$modules` is computed dynamically, not hardcoded
- File: `resources/views/employee/edit.blade.php`
- Check: Same as create.blade.php

## Success Criteria

✅ Employee can be created with 3 new permission checkboxes  
✅ Permissions are saved to `permission_json` column  
✅ Employee with permission can access respective module  
✅ Employee without permission sees "Permission Denied"  
✅ Editing employee updates permissions correctly  
✅ Logout/login respects permission changes  
✅ Super admin has access to all modules regardless  

## Quick Validation Script

Place this in a route for quick testing:

```php
Route::get('/test-permissions', function () {
    $user = Auth::user();
    
    if ($user->type !== 'superAdminEmployee') {
        return 'Must be superAdminEmployee user';
    }
    
    $permissions = json_decode($user->permission_json, true) ?? [];
    
    return [
        'user_type' => $user->type,
        'permissions_json' => $user->permission_json,
        'decoded_permissions' => $permissions,
        'can_manage_legal_library' => in_array('manage legal-library', $permissions) || in_array(3, $permissions),
        'can_manage_document_template' => in_array('manage document-template', $permissions) || in_array(4, $permissions),
        'can_manage_fiscal_resources' => in_array('manage fiscal-resources', $permissions) || in_array(5, $permissions),
    ];
});
```

Then visit `/test-permissions` while logged in as the employee.

---

**Test Date:** _______________  
**Tested By:** _______________  
**Result:** ✓ PASS / ✗ FAIL  
**Notes:** _______________________________________________
