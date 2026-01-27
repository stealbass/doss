# 🧪 Guide de Test - Bibliothèque Pro (3 Fonctionnalités)

## 📱 Accès depuis Flutter

**Navigation** : Bottom Nav → **Bibliothèque Pro** (5ème onglet avec icône 📚)

---

## 1️⃣ CALCULATEURS 🧮

### **Accès**
- Menu **Bibliothèque Pro**
- Cliquer sur **"Calculateurs"**
- ✅ Débloqué pour : **Pro, Professionnel, Cabinet, Entreprise**

### **Route Flutter**
```dart
Navigator.pushNamed(context, '/calculators');
```

### **Écrans Disponibles**
- `calculators_list_screen.dart` - Liste des calculateurs
- `calculator_form_screen.dart` - Formulaire de calcul
- `calculator_result_screen.dart` - Résultats

### **API Backend**
```
GET /api/mobile/calculators?country=CI
POST /api/mobile/calculators/{id}/calculate
GET /api/mobile/calculator-history
```

### **Calculateurs Disponibles**

#### 🇨🇮 **Côte d'Ivoire**
- ✅ Calcul indemnités de licenciement
- ✅ Calcul préavis
- ✅ Congés payés
- ✅ Charges sociales employeur
- ✅ CNPS (cotisations)

#### 🇸🇳 **Sénégal**  
- ✅ Indemnités de licenciement
- ✅ Charges sociales
- ✅ Impôt sur salaire

#### 🇫🇷 **France**
- ✅ Indemnités légales de licenciement
- ✅ Préavis
- ✅ Congés payés

### **Test Complet**

#### **Étape 1 : Liste des calculateurs**
```
1. Ouvrir Bibliothèque Pro
2. Cliquer sur "Calculateurs"
3. Vérifier que la liste s'affiche par pays
4. Filtrer par pays (CI, SN, FR)
```

**Attendu** :
- ✅ Grille de cartes par calculateur
- ✅ Icône + Nom + Description
- ✅ Badge pays (CI, SN, FR)

#### **Étape 2 : Utiliser un calculateur**
```
1. Sélectionner "Indemnités de licenciement"
2. Remplir le formulaire :
   - Salaire mensuel brut : 500000 FCFA
   - Ancienneté : 5 ans
   - Type de licenciement : Économique
3. Cliquer "Calculer"
```

**Attendu** :
- ✅ Formulaire avec champs dynamiques
- ✅ Validation des entrées
- ✅ Résultat affiché avec détails
- ✅ Option télécharger PDF
- ✅ Sauvegarde dans historique

#### **Étape 3 : Historique**
```
1. Menu Hamburger ou onglet "Historique"
2. Voir tous les calculs précédents
3. Recalculer un ancien calcul
```

**Attendu** :
- ✅ Liste chronologique
- ✅ Détails : date, type, montant
- ✅ Bouton "Refaire" ou "Voir détails"

### **Base de Données**

**Tables** :
- `calculator_configs` - Configuration des calculateurs
- `calculator_logs` - Historique des calculs

**Vérifier données** :
```sql
SELECT id, name, country, formula FROM calculator_configs WHERE country = 'CI';
SELECT COUNT(*) FROM calculator_logs WHERE user_id = 201;
```

---

## 2️⃣ VEILLE JURIDIQUE 👁️

### **Accès**
- Menu **Bibliothèque Pro**
- Cliquer sur **"Veille juridique"**
- ✅ Débloqué pour : **Pro, Professionnel, Cabinet, Entreprise**

### **Route Flutter**
```dart
Navigator.pushNamed(context, '/legal-monitoring');
```

### **Écran**
- `legal_monitoring_screen.dart` - Actualités juridiques

### **API Backend**
```
GET /api/mobile/legal-monitoring?country=CI
GET /api/mobile/legal-monitoring/{id}
```

### **Fonctionnalités**

#### **Onglet 1 : Actualités**
- Liste des actualités juridiques récentes
- Filtres par :
  - Catégorie (Droit Civil, Pénal, Commercial, Travail, Fiscal)
  - Juridiction (Côte d'Ivoire, Sénégal, CEDEAO)
  - Date (Aujourd'hui, Cette semaine, Ce mois)

#### **Onglet 2 : Mes Préférences**
- Sélectionner les domaines de veille
- Choisir les juridictions suivies
- Activer/désactiver notifications

### **Test Complet**

#### **Étape 1 : Consulter actualités**
```
1. Ouvrir Bibliothèque Pro
2. Cliquer sur "Veille juridique"
3. Parcourir les actualités
4. Cliquer sur une actualité
```

**Attendu** :
- ✅ Liste d'actualités avec badges "NEW"
- ✅ Titre + Source + Date + Catégorie
- ✅ Résumé de l'actualité
- ✅ Détails complets en cliquant

#### **Étape 2 : Filtrer par catégorie**
```
1. Cliquer sur filtre "Catégorie"
2. Sélectionner "Droit du Travail"
3. Vérifier que seules les actus pertinentes s'affichent
```

**Attendu** :
- ✅ Filtrage dynamique
- ✅ Compteur d'actualités filtrées
- ✅ Badge actif sur filtre sélectionné

#### **Étape 3 : Configurer préférences**
```
1. Onglet "Mes Préférences"
2. Cocher "Droit Civil" et "Droit Commercial"
3. Sélectionner "Côte d'Ivoire" et "Sénégal"
4. Activer notifications push
5. Enregistrer
```

**Attendu** :
- ✅ Sélections sauvegardées
- ✅ Toast de confirmation
- ✅ Actualités personnalisées selon préférences

### **Contenu Exemple**

```
📰 Nouvelle réforme du Code du Travail en Côte d'Ivoire
   Source : Journal Officiel
   Date : 15 janvier 2024
   Catégorie : Droit du Travail
   
   Le gouvernement ivoirien a adopté une série d'amendements...
```

### **Base de Données**

**Tables** :
- `legal_news` - Actualités juridiques
- `user_monitoring_preferences` - Préférences utilisateur

**Vérifier** :
```sql
SELECT title, category, country FROM legal_news ORDER BY published_at DESC LIMIT 10;
```

---

## 3️⃣ ALERTES JURIDIQUES 🔔

### **Accès**
- Menu **Bibliothèque Pro**
- Cliquer sur **"Alertes juridiques"**
- ✅ Débloqué pour : **Pro, Professionnel, Cabinet, Entreprise**

### **Route Flutter**
```dart
Navigator.pushNamed(context, '/legal-alerts');
```

### **Écran**
- `alerts_list_screen.dart` - Liste des alertes

### **API Backend**
```
GET /api/mobile/legal-alerts
POST /api/mobile/legal-alerts/{id}/mark-read
```

### **Fonctionnalités**

#### **Types d'Alertes**
- 🔴 **Urgentes** - Nouvelles lois, décrets immédiats
- 🟡 **Importantes** - Jurisprudence majeure
- 🟢 **Informatives** - Circulaires, notes

#### **Canaux de Notification**
- 📱 Push notification (in-app)
- 📧 Email
- 💬 WhatsApp (Enterprise uniquement)

### **Test Complet**

#### **Étape 1 : Consulter alertes**
```
1. Ouvrir Bibliothèque Pro
2. Cliquer sur "Alertes juridiques"
3. Voir la liste des alertes
4. Badge rouge sur alertes non lues
```

**Attendu** :
- ✅ Liste chronologique des alertes
- ✅ Badge priorité (🔴🟡🟢)
- ✅ Badge "Non lu" sur nouvelles alertes
- ✅ Titre + Date + Résumé

#### **Étape 2 : Lire une alerte**
```
1. Cliquer sur une alerte non lue
2. Lire le contenu complet
3. Badge "Non lu" disparaît automatiquement
```

**Attendu** :
- ✅ Contenu formaté avec markdown
- ✅ Informations structurées :
  - Titre de la loi/décret
  - Date de publication
  - Domaine concerné
  - Résumé exécutif
  - Points clés
- ✅ Bouton "Partager"
- ✅ Marquer comme lu automatiquement

#### **Étape 3 : Filtrer alertes**
```
1. Filtrer par :
   - Toutes / Non lues / Lues
   - Urgente / Importante / Informative
   - Dernières 24h / 7 jours / 30 jours
```

**Attendu** :
- ✅ Filtrage dynamique
- ✅ Compteurs mis à jour
- ✅ Tri pertinent

### **Exemple d'Alerte**

```
🔴 URGENT - Nouveau décret sur le télétravail

📅 Publié le : 15 janvier 2024
📍 Juridiction : Côte d'Ivoire
🏛️ Domaine : Droit du Travail

Le gouvernement a adopté le décret n°2024-015 encadrant 
le télétravail. Principales mesures :

✅ Accord écrit obligatoire
✅ Prise en charge frais professionnels
✅ Droit à la déconnexion
✅ Contrôle du temps de travail

Entrée en vigueur : 1er février 2024
```

### **Base de Données**

**Tables** :
- `legal_alerts` - Alertes
- `legal_alert_recipients` - Tracking des envois
- `user_alert_reads` - Suivi des lectures

**Vérifier données** :
```sql
SELECT * FROM legal_alerts WHERE is_mobile_visible = 1 ORDER BY created_at DESC LIMIT 5;
SELECT COUNT(*) as unread FROM user_alert_reads WHERE user_id = 201 AND read_at IS NULL;
```

**Si aucune alerte visible** :
```sql
UPDATE legal_alerts SET is_mobile_visible = 1;
```

---

## ✅ Checklist de Test Globale

### **Calculateurs**
- [ ] Liste s'affiche correctement
- [ ] Filtrage par pays fonctionne
- [ ] Formulaire de calcul s'ouvre
- [ ] Validation des champs
- [ ] Résultat s'affiche avec détails
- [ ] Téléchargement PDF possible
- [ ] Historique sauvegardé

### **Veille Juridique**
- [ ] Actualités s'affichent
- [ ] Badges "NEW" visibles sur récentes
- [ ] Détails d'une actualité accessibles
- [ ] Filtres par catégorie fonctionnent
- [ ] Filtres par juridiction fonctionnent
- [ ] Préférences sauvegardées
- [ ] Notifications configurables

### **Alertes Juridiques**
- [ ] Alertes listées par date décroissante
- [ ] Badges priorité affichés (🔴🟡🟢)
- [ ] Badge "Non lu" visible
- [ ] Lecture marque automatiquement comme lu
- [ ] Filtrage par statut fonctionne
- [ ] Contenu formaté correctement
- [ ] Partage disponible

---

## 🔧 Dépannage Rapide

### **Problème : "Aucun calculateur disponible"**
```sql
-- Vérifier présence de calculateurs
SELECT COUNT(*) FROM calculator_configs;

-- Ajouter des calculateurs de test
INSERT INTO calculator_configs (name, country, formula, inputs) VALUES
('Indemnités de licenciement', 'CI', 'salary * years * 0.5', '[]');
```

### **Problème : "Aucune actualité"**
```sql
-- Vérifier présence d'actualités
SELECT COUNT(*) FROM legal_news;

-- Ajouter actualité de test
INSERT INTO legal_news (title, content, category, country, published_at) VALUES
('Test Actualité', 'Contenu test', 'Droit Civil', 'CI', NOW());
```

### **Problème : "Aucune alerte"**
```sql
-- Vérifier alertes visibles
SELECT COUNT(*) FROM legal_alerts WHERE is_mobile_visible = 1;

-- Rendre toutes les alertes visibles
UPDATE legal_alerts SET is_mobile_visible = 1;
```

### **Problème : "Fonctionnalité verrouillée"**
```sql
-- Vérifier plan utilisateur
SELECT id, name, email, plan FROM users WHERE id = 201;

-- Mettre à jour en plan Pro
UPDATE users SET plan = 'Pro' WHERE id = 201;
```

---

## 📊 Résumé des Accès par Plan

| Fonctionnalité | Essentiel | Standard | Pro | Cabinet | Entreprise |
|----------------|-----------|----------|-----|---------|------------|
| **Calculateurs** | ❌ | ❌ | ✅ | ✅ | ✅ |
| **Veille Juridique** | ❌ | ❌ | ✅ | ✅ | ✅ |
| **Alertes Juridiques** | ❌ | ❌ | ✅ | ✅ | ✅ |

---

## 🚀 État d'Implémentation

### **Backend Laravel**
- ✅ Routes API définies
- ✅ Controllers créés
- ✅ Models configurés
- ✅ Migrations exécutées
- ✅ Permissions implémentées

### **Frontend Flutter**
- ✅ Screens créés
- ✅ Providers configurés
- ✅ Routes définies
- ✅ UI complète
- ✅ Intégration API

### **Tests à Effectuer**
1. ✅ Navigation depuis Bibliothèque Pro
2. ⏳ Chargement des données depuis API
3. ⏳ Affichage UI correct
4. ⏳ Interactions utilisateur
5. ⏳ Sauvegarde des données

---

**Prêt pour les tests !** 🎯

Commencez par **Calculateurs**, puis **Veille Juridique**, et enfin **Alertes Juridiques**.
