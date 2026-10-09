# Utiliser l’interface web

Le frontend et le backend sont séparés en deux dossiers : `frontend/` contient l’interface statique (HTML, CSS, JavaScript) et `public/` contient le point d’entrée PHP et l’API. Le frontend n’a ni framework ni étape de compilation.

## Ouvrir l’application sous WAMP

1. Place le dossier du projet dans `C:\wamp64\www\`.
2. Dans l’invite de commandes, ouvre ce dossier puis installe les bibliothèques PHP : `composer install`.
3. Si tu installes le projet depuis l’archive, copie `.env.example` vers `.env`, puis renseigne les paramètres MariaDB. Ne publie jamais `.env`.
4. Démarre WAMP et vérifie que son icône est verte.
5. Ouvre dans le navigateur `http://localhost/NOM_DU_DOSSIER/frontend/` en remplaçant `NOM_DU_DOSSIER` par le nom réel du dossier.
6. L’interface utilise directement la base configurée dans `.env` via les routes PHP existantes.

Le frontend utilise `../public` dans `frontend/assets/js/config.js` pour joindre l’API sur le même domaine WAMP. Le projet peut porter un nom différent de `personal_finance_tracker`. L’API ne configure pas CORS : héberge le frontend et le backend sur le même domaine, sauf si tu ajoutes et configures explicitement CORS côté PHP.

## Fonctions disponibles

- Tableau de bord avec soldes, revenus, dépenses, solde net et aperçu des budgets.
- Choix du mois pour le résumé et l’historique.
- Création, modification et suppression des comptes et catégories.
- Création, modification et suppression des transactions, avec filtres par compte et catégorie.
- Avertissement budgétaire après enregistrement : un dépassement n’annule pas la transaction.
- Écrans de chargement, d’erreur et listes vides.

Les données affichées viennent de l’API PHP. L’interface n’ajoute aucune donnée de démonstration.

## Routes réellement utilisées

L’interface suit les routes présentes dans `routes/api.php` :

- Comptes : `GET` et `POST /accounts`, puis `GET`, `PATCH` et `DELETE /accounts/{id}`.
- Catégories : `GET` et `POST /categories`, puis `GET`, `PATCH` et `DELETE /categories/{id}`.
- Transactions : `GET` et `POST /transaction`, puis `GET`, `PATCH` et `DELETE /transaction/{id}`.
- Historique d’un mois : `GET /transaction/month/YYYY-MM`.
- Résumé : `GET /summary?month=YYYY-MM`.

Le backend impose une catégorie pour une transaction dans le schéma fourni en capture; le formulaire la demande donc. Le backend actuel ne permet pas d’effacer une limite mensuelle déjà définie : on peut modifier son montant, et l’interface prévient l’utilisateur de cette limite.

## Déploiement

Le frontend statique se trouve dans `frontend/`. Le fichier `.htaccess` de `public/` envoie les routes API non physiques vers `public/index.php`. Le dossier `public/` est la racine publique du backend PHP; `.env` reste à la racine du projet, hors de `public/`. L’archive contient uniquement `.env.example`, jamais de fichier `.env`.
