# 🔄 Guide de Restauration & Contrôle de Version

## ✅ Status: Votre projet est maintenant protégé avec Git + Backups

### 3 Niveaux de Protection

#### 1️⃣ **Git Version Control** (Recommandé)
Utilisé pour chaque modification avec historique complet.

```bash
# Voir l'historique des commits
git log --oneline

# Revenir à un commit spécifique
git checkout COMMIT_HASH

# Voir les changements
git diff

# Créer une branche avant de faire des changements risky
git checkout -b feature/prompt-engineering-v2
```

#### 2️⃣ **Snapshots Nommés** (Points de Sauvegarde Rapides)
Pour marquer des points importants.

```bash
# Créer un snapshot nommé (exemple)
git tag -a "WORKING-v1.0-before-prompt-eng" -m "État stable avant optimisations"
git push origin "WORKING-v1.0-before-prompt-eng"

# Lister tous les snapshots
git tag

# Restaurer à un snapshot
git checkout "WORKING-v1.0-before-prompt-eng"
```

#### 3️⃣ **Backup ZIP** (Restauration d'Urgence)
Archive complète du projet.

```bash
# Créer un backup ZIP (Windows PowerShell)
Compress-Archive -Path ".\doss-genspark_ai_developer" -DestinationPath "backup-WORKING-v1.0.zip"

# Restaurer à partir d'un backup ZIP
Expand-Archive -Path "backup-WORKING-v1.0.zip" -DestinationPath "."
```

---

## 🎯 Workflow Recommandé pour les Améliorations

### Avant de Commencer les Modifications

```bash
# 1. Créer une branche pour l'amélioration
git checkout -b feature/prompt-engineering-optimization

# 2. Vérifier qu'on est sur la bonne branche
git branch
# Vous devriez voir: * feature/prompt-engineering-optimization

# 3. Faire un snapshot du point de départ
git tag -a "BEFORE-prompt-eng-v1" -m "État avant optimisations"
```

### Pendant les Modifications

```bash
# Après chaque changement important, committer
git add fichier_modifié.dart
git commit -m "✨ Amélioration: [description]"

# Ou committer tous les changements
git add -A
git commit -m "✨ Amélioration: [description]"
```

### Si Quelque Chose Casse

```bash
# Option 1: Voir les changements non commitués
git status
git diff

# Option 2: Annuler les changements d'un fichier
git checkout -- fichier_problématique.dart

# Option 3: Revenir au dernier commit
git reset --hard HEAD

# Option 4: Revenir à un commit spécifique
git reset --hard COMMIT_HASH

# Option 5: Revenir à une branche stable
git checkout main
```

### Après que Tout Marche

```bash
# Fusionner la branche feature dans main
git checkout main
git merge feature/prompt-engineering-optimization

# Créer un snapshot du nouvel état stable
git tag -a "WORKING-v1.1-after-prompt-eng" -m "Prompt engineering optimisé"

# Supprimer la branche feature (optionnel)
git branch -d feature/prompt-engineering-optimization
```

---

## 📋 Commandes Git Essentielles

### Voir l'État
```bash
git status              # Voir les fichiers modifiés
git log --oneline       # Voir l'historique
git diff                # Voir les changements non commitués
git branch              # Lister les branches
```

### Sauvegarder
```bash
git add fichier.dart    # Ajouter un fichier
git add -A              # Ajouter tous les fichiers
git commit -m "message" # Créer un commit
git tag -a "nom"       # Créer un snapshot
```

### Restaurer
```bash
git checkout -- fichier.dart              # Annuler changements d'un fichier
git reset --hard HEAD                     # Revenir au dernier commit
git checkout COMMIT_HASH                  # Aller à un commit spécifique
git reset --hard COMMIT_HASH              # Revenir à un commit spécifique
```

### Branches
```bash
git branch nom-branche                    # Créer une branche
git checkout nom-branche                  # Aller à une branche
git checkout -b nom-branche               # Créer et aller à une branche
git merge nom-branche                     # Fusionner une branche
git branch -d nom-branche                 # Supprimer une branche
```

---

## 🚨 Scénarios d'Urgence

### Scenario 1: "J'ai modifié plusieurs fichiers et tout casse!"
```bash
# Voir quels fichiers ont été modifiés
git status

# Annuler TOUS les changements
git reset --hard HEAD

# Vérifier que c'est revenu à normal
git status  # Devrait dire "nothing to commit"
```

### Scenario 2: "Je veux voir ce que j'ai changé avant de committer"
```bash
# Voir les changements
git diff

# Voir les changements d'un fichier spécifique
git diff fichier.dart
```

### Scenario 3: "Je veux revenir à la version d'hier"
```bash
# Voir l'historique
git log --oneline

# Revenir à un commit spécifique
git reset --hard abc1234  # où abc1234 est le hash du commit

# Ou utiliser le tag
git checkout WORKING-v1.0-before-prompt-eng
```

### Scenario 4: "J'ai committé par erreur, comment revenir?"
```bash
# Voir le dernier commit
git log -1

# Annuler le dernier commit mais garder les changements
git reset --soft HEAD~1

# Annuler le dernier commit ET les changements
git reset --hard HEAD~1
```

---

## 📦 Snapshot Actuel (Créé)

**Checkpoint**: `CHECKPOINT-WORKING` (État actuel)
- ✅ Tous les tests passent
- ✅ Rating prompt Flutter implémenté et testé
- ✅ Document extraction fonctionnelle
- ✅ Pinecone indexing automatique
- ✅ Suppression documents avec cleanup Pinecone
- ✅ État STABLE avant modifications

**Pour revenir à cet état**:
```bash
# Voir tous les commits
git log --oneline

# Revenir à ce commit spécifique
git reset --hard COMMIT_HASH
```

---

## 🎯 Prochaines Étapes pour Prompt Engineering

### Avant de Commencer
```bash
# 1. Créer une branche
git checkout -b feature/prompt-engineering

# 2. Créer un snapshot avant
git tag -a "BEFORE-prompt-eng" -m "État avant optimisations"

# 3. Commencer les modifications...
```

### Pendant le Travail
```bash
# Après chaque amélioration, committer
git add -A
git commit -m "✨ Amélioration Prompt: [description]"
```

### Si Ça Casse
```bash
# Revenir rapidement
git reset --hard HEAD~1  # Annuler le dernier commit
git reset --hard BEFORE-prompt-eng  # Ou revenir au snapshot
```

### Quand Tout Marche
```bash
git merge main  # Fusionner dans la branche principale
git tag -a "WORKING-v1.1" -m "Prompt engineering complété"
```

---

## 💡 Bonnes Pratiques

1. **Committer souvent** - Un changement = un commit
2. **Messages clairs** - Décrire ce qui a changé et pourquoi
3. **Tester avant de committer** - S'assurer que tout marche
4. **Créer des branches** - Pour les améliorations, pas directement sur main
5. **Utiliser des tags** - Pour les points de sauvegarde importants

---

## 📞 Aide Rapide

| Problème | Solution |
|----------|----------|
| "Tout casse!" | `git reset --hard HEAD` |
| "Je veux voir l'historique" | `git log --oneline` |
| "Je veux revenir à hier" | `git reset --hard COMMIT_HASH` |
| "Quelle branche suis-je?" | `git branch` |
| "Mes changements?" | `git status` ou `git diff` |
| "Comment annuler un commit?" | `git reset --hard HEAD~1` |

---

**Vous êtes maintenant protégé avec Git! Prêt pour améliorer le Prompt Engineering? 🚀**
