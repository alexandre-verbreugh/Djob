# Djob

Djob est un outil d'automatisation pour la recherche d'emploi. Il scanne les offres d'emploi depuis France Travail et des flux RSS, les analyse, et aide à la gestion des candidatures.

## Fonctionnalités

- **Scan d'offres** : Récupère les offres depuis l'API France Travail et des flux RSS configurés.
- **Analyse des jobs** : Analyse le contenu des offres pour attribuer un score de pertinence et générer un résumé.
- **Génération de lettre de motivation** : (Actuellement) Génère une ébauche de lettre de motivation pour chaque offre pertinente.

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- Symfony CLI

### Étapes

1.  Cloner le dépôt :
    ```bash
    git clone <votre-depot>
    cd Djob
    ```

2.  Installer les dépendances :
    ```bash
    composer install
    ```

3.  Configurer l'environnement :
    - Copiez le fichier `.env` vers `.env.local` :
      ```bash
      cp .env .env.local
      ```
    - Remplissez les variables d'environnement nécessaires (clés API France Travail, base de données, etc.) dans `.env.local`.

4.  Créer la base de données et les tables :
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

## Utilisation

Pour lancer la recherche d'emploi ("chasse") :

```bash
php bin/console app:hunt
```

Cette commande va :
1.  Scanner les nouvelles offres.
2.  Les analyser et leur attribuer un score.
3.  Afficher les résultats dans la console.

## Roadmap (Prochaines étapes)

- [ ] **Affiner la génération de lettres** : Ne plus générer des lettres pour toutes les offres systématiquement.
- [ ] **Diversifier les sources** : Ajouter d'autres sites d'offres d'emploi.
- [ ] **Notifications** : Être informé par email des nouvelles offres pertinentes.
- [ ] **Export PDF** : Télécharger la lettre de motivation générée au format PDF.
