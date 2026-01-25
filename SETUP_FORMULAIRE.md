# Préparation du formulaire Laravel

## Étapes à suivre:

### 1. Exécuter la migration
```bash
php artisan migrate
```

### 2. Créer le lien symbolique pour les fichiers publics (photos)
```bash
php artisan storage:link
```

### 3. Configuration .env
Assurez-vous que votre `.env` contient:
```
FILESYSTEM_DISK=public
```

### 4. Permissions des dossiers
```bash
chmod -R 775 storage/app/public
chmod -R 775 bootstrap/cache
```

## Fichiers créés/modifiés:

✅ **Contrôleur**: `app/Http/Controllers/PatientController.php`
- Méthode `create()`: Affiche le formulaire
- Méthode `store()`: Enregistre les données avec validation
- Méthode `show()`: Affiche un dossier
- Méthode `edit()`: Formulaire d'édition
- Méthode `update()`: Met à jour les données
- Méthode `destroy()`: Supprime un dossier

✅ **Modèle**: `app/Models/Patient.php`
- Tous les champs du formulaire
- Casting des dates en JSON
- Attributs personnalisés (nom complet, âge)

✅ **Routes**: `routes/web.php`
- GET `/nouveau-dossier` → Create
- POST `/patients` → Store
- GET `/patients/{id}` → Show
- GET `/patients/{id}/edit` → Edit
- PUT `/patients/{id}` → Update
- DELETE `/patients/{id}` → Destroy

✅ **Migration**: `database/migrations/...create_patients_table.php`
- Table patients avec tous les champs
- Indexes pour les recherches
- Contrainte unique sur l'email

## Fonctionnalités du formulaire:

✅ Token CSRF automatique
✅ Validation côté serveur
✅ Messages d'erreur affichés
✅ Valeurs old() conservées après soumission
✅ Téléchargement de photo avec stockage
✅ Sélections en cascade (Province → Territoire → Commune)
✅ Tous les inputs ont des noms (name=)
✅ Attributs required/optional

## Pour tester:

1. Créer un utilisateur
2. Se connecter
3. Aller à `/nouveau-dossier`
4. Remplir le formulaire
5. Soumettre

Le dossier sera créé et vous serez redirigé vers la page de détails!
