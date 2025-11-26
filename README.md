# Djob

Djob est une application web intelligente pour la recherche d'emploi. Elle permet de rechercher des offres en temps réel via l'API France Travail, de générer des lettres de motivation sur-mesure grâce à l'IA (DeepSeek), et de gérer ses candidatures favorites.

## Fonctionnalités

- **Recherche en temps réel** : Moteur de recherche connecté directement à l'API France Travail (par mot-clé et département).
- **Détails enrichis** : Affichage complet des offres (compétences, contact, expérience).
- **IA à la demande** : Génération d'une lettre de motivation ultra-personnalisée via DeepSeek (uniquement pour les offres sélectionnées).
- **Gestion des Candidatures** : Sauvegarde automatique des offres intéressantes et des lettres générées dans une base de données MySQL ("Shortlist").
- **Tableau de bord** : Vue centralisée pour retrouver ses candidatures sauvegardées.

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- MySQL
- Composer
- Symfony CLI

### Étapes

1.  **Cloner le dépôt :**
    ```bash
    git clone <votre-depot>
    cd Djob
    ```

2.  **Installer les dépendances :**
    ```bash
    composer install
    ```

3.  **Configurer l'environnement :**
    Créez un fichier `.env.local` à la racine et ajoutez vos clés :
    ```dotenv
    # Base de données
    DATABASE_URL="mysql://USER:PASS@127.0.0.1:3306/job_hunter?serverVersion=8.0&charset=utf8mb4"

    # API IA
    DEEPSEEK_API_KEY="sk-..."

    # API France Travail (Offres d'emploi v2)
    FRANCE_TRAVAIL_CLIENT_ID="Votre_Client_ID"
    FRANCE_TRAVAIL_SECRET="Votre_Client_Secret"
    ```

4.  **Configurer le profil candidat :**
    Ouvrez `config/services.yaml` et modifiez le paramètre `app.candidate_profile` pour adapter la lettre de motivation à votre profil :
    ```yaml
    parameters:
        app.candidate_profile: |
            Développeur Symfony passionné avec 3 ans d'expérience. 
            Expertise en API Platform et Vue.js. 
            Ton professionnel mais dynamique.
    ```

5.  **Base de données :**
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

## Utilisation

### Mode Web (Recommandé)

Lancez le serveur Symfony :
```bash
symfony server:start
```

Rendez-vous sur `http://127.0.0.1:8000`.

1.  **Recherchez** : Utilisez le formulaire (ex: "Développeur Symfony", "75").
2.  **Analysez** : Cliquez sur une offre pour voir les détails.
3.  **Générez** : Cliquez sur "✨ Générer Lettre". L'offre est alors automatiquement sauvegardée dans vos favoris.
4.  **Retrouvez** : Consultez vos offres sauvegardées dans l'onglet "Mes Candidatures".

### Mode Console (Script de fond)

Une commande existe pour scanner automatiquement (via RSS ou API) et remplir la base (utile pour des tâches CRON) :

```bash
php bin/console app:hunt
```

## Roadmap (Prochaines étapes)

- [x] Recherche en direct France Travail (API)
- [x] Génération de lettre "On Demand"
- [x] Sauvegarde BDD (Favoris)
- [ ] Ajout de sources : Intégrer WeLoveDevs et Welcome to the Jungle.
- [ ] Export PDF : Télécharger la lettre de motivation générée au format PDF.
- [ ] Filtres avancés : Recherche par salaire, type de contrat (CDI/Freelance).
- [ ] Déploiement : Mise en production sur serveur.
```
