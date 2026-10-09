# Personal Finance Tracker

Application de suivi des finances personnelles avec une interface web statique, une API PHP et une base MariaDB.

> Projet préparé avec WAMP64 sous Windows. Les commandes ci-dessous sont à exécuter dans l’invite de commandes, à la racine du projet (le dossier qui contient composer.json).

## Fonctionnalités

- Créer, consulter, modifier et supprimer des comptes et des catégories.
- Enregistrer des revenus et des dépenses, avec un compte et une catégorie.
- Mettre à jour le solde du compte après une création, modification ou suppression de transaction.
- Consulter les transactions d’un mois et un résumé mensuel.
- Afficher ces données dans l’interface web.

Le dépassement d’une limite mensuelle est signalé, mais ne bloque pas la transaction. Le projet ne comporte pas d’authentification multi-utilisateur : n’y stockez pas de vraies données financières sur un serveur public.

## Technologies

- PHP 8.3, PDO et MariaDB/MySQL
- Composer et vlucas/phpdotenv
- PHPUnit 12.5
- HTML, CSS et JavaScript, sans étape de compilation du frontend

## Architecture

    frontend/                  Interface web statique (HTML, CSS, JavaScript)
    public/                     Point d’entrée PHP de l’API et règles Apache
    routes/api.php              Déclaration des routes HTTP
    src/Controllers/            Traitement des requêtes et réponses JSON
    src/Commands/               Opérations d’écriture et règles métier
    src/Queries/                Lectures et résumés
    src/Repositories/            Accès à MariaDB avec PDO
    src/Database/               Connexion PDO
    tests/Unit/                 Tests unitaires sans MariaDB
    tests/Integration/          Tests MariaDB et HTTP
    tests/fixtures/             Schéma de test et routeur HTTP de test
    database/schema.sql         Schéma de la base normale (à ajouter avant publication)

Le frontend et l’API doivent être servis sur le même domaine. Le frontend appelle l’API avec le chemin ../public défini dans frontend/assets/js/config.js.

## Installation locale avec WAMP64

### Prérequis

- WAMP64 avec Apache et MariaDB démarrés.
- PHP 8.3 et Composer accessibles dans l’invite de commandes.
- Extensions PHP pdo_mysql et mbstring activées.

Vérifiez PHP et Composer :

    php -v
    composer --version

### Installer le code

1. Placez le dossier du projet dans C:\wamp64\www\.
2. Ouvrez l’invite de commandes dans ce dossier.
3. Installez les dépendances :

    composer install

Composer crée le dossier vendor/. Il ne faut pas publier ce dossier.

### Créer et configurer la base de l’application

1. Dans phpMyAdmin, créez une base appelée finance_tracker.
2. Le dépôt doit fournir le fichier database/schema.sql contenant les instructions CREATE TABLE, sans données personnelles.
3. Dans phpMyAdmin, sélectionnez finance_tracker, ouvrez Importer, choisissez database/schema.sql et lancez l’importation.

Si database/schema.sql n’est pas présent dans le projet reçu, demandez le schéma d’installation avant de continuer. Le fichier tests/fixtures/schema.test.sql est réservé à la base de test : ne l’importez pas dans la base normale.

Copiez le modèle de configuration :

    copy .env.example .env

Ouvrez .env à la racine du projet et adaptez les paramètres à votre MariaDB :

    DB_HOST=localhost
    DB_PORT=3306
    DB_DATABASE=finance_tracker
    DB_USERNAME=root
    DB_PASSWORD=

Le port WAMP est souvent 3306. Utilisez les identifiants configurés sur votre installation. Le fichier .env contient la configuration privée et ne doit pas être publié.

### Ouvrir l’application

Vérifiez que WAMP est démarré et que son icône est verte. Ouvrez ensuite :

    http://localhost/NOM_DU_DOSSIER/frontend/

Remplacez NOM_DU_DOSSIER par le nom du dossier placé dans C:\wamp64\www. Par exemple :

    http://localhost/personal_finance_tracker/frontend/

Pour vérifier l’API, vous pouvez ouvrir :

    http://localhost/NOM_DU_DOSSIER/public/accounts

## Routes HTTP disponibles

Les chemins suivants sont relatifs au dossier public :

| Méthode | Chemin | Fonction |
|---|---|---|
| GET | /accounts | Lister les comptes |
| POST | /accounts | Créer un compte |
| GET | /accounts/{id} | Consulter un compte |
| PATCH | /accounts/{id} | Modifier un compte |
| DELETE | /accounts/{id} | Supprimer un compte |
| GET | /categories | Lister les catégories |
| POST | /categories | Créer une catégorie |
| GET | /categories/{id} | Consulter une catégorie |
| PATCH | /categories/{id} | Modifier une catégorie |
| DELETE | /categories/{id} | Supprimer une catégorie |
| GET | /transaction | Lister les transactions |
| POST | /transaction | Créer une transaction |
| GET | /transaction/{id} | Consulter une transaction |
| PATCH | /transaction/{id} | Modifier une transaction |
| DELETE | /transaction/{id} | Supprimer une transaction |
| GET | /transaction/month/YYYY-MM | Transactions d’un mois |
| GET | /account/{id}/transactions | Transactions d’un compte |
| GET | /summary?month=YYYY-MM | Résumé mensuel |

## Lancer les tests unitaires

Depuis le dossier principal du projet :

    composer test

Ces tests vérifient les règles métier des comptes, catégories et transactions. Ils n’ont besoin ni de MariaDB ni du serveur HTTP.

## Lancer les tests MariaDB et HTTP

Les tests d’intégration utilisent une base séparée nommée finance_tracker_test. Ils ne doivent jamais utiliser finance_tracker, car ils créent et nettoient des données de test.

1. Dans phpMyAdmin, créez la base vide finance_tracker_test.
2. Sélectionnez-la, ouvrez Importer, puis importez tests/fixtures/schema.test.sql.
3. À la racine du projet, créez votre configuration de test :

    copy .env.testing.example .env.testing

4. Ouvrez .env.testing et vérifiez les paramètres. Adaptez l’utilisateur et le mot de passe à votre WAMP :

    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=finance_tracker_test
    DB_USERNAME=root
    DB_PASSWORD=
    TEST_BASE_URL=http://127.0.0.1:8000/personal_finance_tracker/public

Ne publiez jamais .env.testing. Pour les commandes suivantes, gardez la valeur TEST_BASE_URL indiquée.

5. Ouvrez une première invite de commandes à la racine du projet et démarrez le serveur HTTP de test :

    php -S 127.0.0.1:8000 tests/fixtures/http-router.php

Laissez cette fenêtre ouverte. Ce serveur PHP de test est distinct d’Apache WAMP.

6. Ouvrez une deuxième invite de commandes au même endroit et lancez :

    composer test:integration

Ces tests vérifient l’accès à MariaDB et envoient de vraies requêtes HTTP à l’API. Si la configuration, MariaDB ou le serveur HTTP manque, les tests d’intégration peuvent être ignorés. Les tests unitaires restent exécutables avec composer test.

## Publier le projet sur GitHub

Publiez le code, les tests, composer.json, composer.lock, les fichiers de configuration d’exemple, les instructions et les scripts SQL sans données personnelles.

Le dépôt doit contenir un fichier .gitignore avec au minimum :

    .env
    .env.testing
    /vendor/

N’ajoutez pas .env ou .env.testing à GitHub : ils contiennent les paramètres propres à votre ordinateur. Les fichiers .env.example et .env.testing.example sont des modèles publics ; ils ne doivent contenir aucun mot de passe privé. N’envoyez pas de sauvegarde SQL avec de vraies données.

## Choix d’architecture et limites

- Les opérations de lecture (Queries) et d’écriture (Commands) sont séparées. C’est une séparation inspirée de CQRS, simplifiée pour ce projet.
- Les tests unitaires sont isolés. Les tests d’intégration ont besoin d’une base et d’un serveur HTTP.
- Le frontend et l’API doivent être servis sur le même domaine ; CORS n’est pas configuré.
- L’application ne gère pas plusieurs utilisateurs et ne doit pas encore être utilisée pour des finances réelles.
- Une prochaine étape serait de renforcer la sécurité, d’ajouter une authentification et de vérifier le déploiement sur un hébergeur PHP/MariaDB.

## Auteur

Projet réalisé dans le cadre d’une évaluation et d’un apprentissage du développement d’une application de gestion des finances personnelles.

