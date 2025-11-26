# 🔧 Fix : Preview PDF 404 avec R2

## 🔴 Problème Identifié

### **Symptômes**
- ✅ Les uploads vers R2 fonctionnent correctement
- ✅ Le téléchargement (Download) des PDFs fonctionne
- ❌ Le **preview** (visualisation inline) des PDFs affiche **404 Not Found**

### **Cause**

La méthode `streamDocument()` dans `UserLegalLibraryController` cherchait le fichier en **local** au lieu de le récupérer depuis **R2** :

```php
// AVANT (Broken)
public function streamDocument($id)
{
    // ...
    $filePath = storage_path('app/public/' . $document->file_path);
    
    if (!file_exists($filePath)) {
        abort(404, 'File not found');  // ← 404 car fichier pas en local!
    }

    return response()->file($filePath, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="...'
    ]);
}
```

**Problème** :
- Les fichiers sont sur **R2**, pas en local
- `storage_path('app/public/...')` cherche en local
- Le fichier n'existe pas localement → **404**

---

## ✅ Solution Implémentée

### **Modification** : `app/Http/Controllers/UserLegalLibraryController.php`

La méthode `streamDocument()` détecte maintenant le storage configuré et redirige vers R2 si nécessaire :

```php
// APRÈS (Fixed)
public function streamDocument($id)
{
    if (Auth::user()->can('view legal library')) {
        // Check if user has free plan
        if (Auth::user()->hasFreePlan()) {
            abort(403, 'Cette fonctionnalité nécessite un abonnement premium.');
        }

        $document = LegalDocument::find($id);
        
        if (!$document) {
            abort(404, 'Document not found');
        }

        // Get storage setting
        $settings = \App\Models\Utility::settings();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        
        if ($storageSetting === 'r2') {
            // For R2: redirect to public URL for inline preview
            $url = \App\Models\Utility::get_file($document->file_path);
            
            if (empty($url)) {
                abort(404, 'File not found on R2');
            }
            
            // Redirect to R2 public URL (browser will display PDF inline)
            return redirect($url);
        } else {
            // For local storage: stream file directly
            $filePath = storage_path('app/public/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                abort(404, 'File not found');
            }

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
            ]);
        }
    } else {
        abort(403, 'Permission Denied');
    }
}
```

### **Logique**

1. **Détecte le storage configuré** (`local` ou `r2`)
2. **Si R2** :
   - Génère l'URL publique R2 via `Utility::get_file()`
   - Redirige vers cette URL
   - Le navigateur affiche le PDF inline depuis R2
3. **Si local** :
   - Stream le fichier directement depuis le disque local
   - Utilisation de `response()->file()` comme avant

---

## 🎯 Cohérence avec `downloadDocument()`

La méthode `downloadDocument()` fonctionnait déjà avec R2 :

```php
public function downloadDocument($id)
{
    // ...
    $storageSetting = $settings['storage_setting'] ?? 'local';
    
    if ($storageSetting === 'r2') {
        // For R2: redirect to public URL
        $url = \App\Models\Utility::get_file($document->file_path);
        return redirect($url);
    } else {
        // For local storage: direct download
        $filePath = storage_path('app/public/' . $document->file_path);
        return response()->download($filePath, $document->file_name);
    }
}
```

**Maintenant**, `streamDocument()` utilise la **même logique** pour assurer la cohérence.

---

## 🧪 Test de Vérification

### **Étape 1 : Déploiement**
```bash
ssh dossypro@ssh-dossypro.alwaysdata.net
cd ~/public_html
git pull origin main
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **Étape 2 : Test Upload**
1. **Super Admin → Legal Library → Catégorie**
2. **Add Document**
3. Uploadez un PDF de test (`test_preview_r2.pdf`)
4. **Save**

### **Étape 3 : Vérification R2**
- **Cloudflare R2 Dashboard → dossy-pro-documents → legal_documents/**
- **✅ Le fichier doit apparaître**

### **Étape 4 : Test Preview (CRITIQUE)**
1. Connectez-vous en tant qu'**utilisateur** (pas admin)
2. **Bibliothèque Juridique → Catégorie**
3. Cliquez sur le document `test_preview_r2.pdf`
4. **La page de visualisation s'ouvre**
5. **✅ Le PDF doit s'afficher dans l'iframe** (pas de 404)
6. **URL du PDF dans l'iframe** : `https://files.dossypro.com/legal_documents/...`

### **Étape 5 : Test Download**
1. Sur la même page
2. Cliquez sur le bouton **"Download Document"**
3. **✅ Le PDF doit se télécharger** correctement

---

## 📊 Flux Avant/Après

### **AVANT (Preview Broken)**
```
User clique sur document
    ↓
Route: user.legal-library.stream
    ↓
streamDocument() cherche en local
    ↓
storage_path('app/public/legal_documents/file.pdf')
    ↓
File not exists (fichier sur R2, pas en local)
    ↓
❌ abort(404, 'File not found')
    ↓
User voit: 404 Not Found
```

### **APRÈS (Preview Fixed)**
```
User clique sur document
    ↓
Route: user.legal-library.stream
    ↓
streamDocument() détecte storage_setting = 'r2'
    ↓
Utility::get_file() génère URL R2
    ↓
return redirect('https://files.dossypro.com/legal_documents/file.pdf')
    ↓
Navigateur charge le PDF depuis R2
    ↓
✅ PDF s'affiche inline dans l'iframe
```

---

## 📦 Fichiers Modifiés

### **app/Http/Controllers/UserLegalLibraryController.php**
- ✅ Méthode `streamDocument()` mise à jour
- ✅ Détection du storage configuré (R2 ou local)
- ✅ Redirection vers URL R2 publique si R2
- ✅ Fallback vers local si storage local

---

## 🔍 Vue Utilisateur

**Fichier** : `resources/views/user-legal-library/view.blade.php`

Ligne 89-94 :
```blade
<iframe 
    src="{{ route('user.legal-library.stream', $document->id) }}" 
    type="application/pdf" 
    width="100%" 
    height="100%"
    style="border: 1px solid #ddd; border-radius: 5px;">
```

**Comportement** :
1. L'iframe charge la route `user.legal-library.stream`
2. Le contrôleur redirige vers l'URL R2 publique
3. Le navigateur affiche le PDF inline depuis R2

---

## ✅ Résultat Final

Après cette correction :

✅ **Upload** → Cloudflare R2  
✅ **Preview** → Affichage inline depuis R2 (pas de 404)  
✅ **Download** → Téléchargement depuis R2  
✅ **URLs** → `https://files.dossypro.com/legal_documents/...`  
✅ **Cohérence** → Toutes les méthodes (stream/download) gèrent R2 de la même manière

---

## 📋 Checklist de Validation

- [ ] `git pull origin main` effectué
- [ ] Cache Laravel vidé
- [ ] Upload d'un document de test
- [ ] Fichier visible dans R2 Dashboard
- [ ] Page de visualisation accessible
- [ ] **Preview PDF s'affiche** (pas de 404) ✅
- [ ] Download fonctionne ✅
- [ ] URL commence par `https://files.dossypro.com/`

---

## 🎯 Résumé Technique

**Problème** : `streamDocument()` cherchait le fichier en local alors qu'il est sur R2

**Solution** : Détection du storage et redirection vers URL R2 publique

**Inspiration** : Même logique que `downloadDocument()` qui fonctionnait déjà

**Résultat** : Preview et Download utilisent tous les deux R2 correctement

---

**Date** : 2025-11-26  
**Projet** : Dossy Pro - Fix Preview R2  
**Pull Request** : https://github.com/stealbass/doss/pull/10  
**Fichier modifié** : `app/Http/Controllers/UserLegalLibraryController.php`
