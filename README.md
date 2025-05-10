# LEVELOP - Plateforme Communautaire Gaming

Une plateforme communautaire gaming complète qui combine e-commerce, forums de discussion, gestion d'événements, services de coaching et assistance gaming basée sur l'IA pour les joueurs.

## Table des Matières

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Contribuer](#contribuer)
- [Licence](#licence)
- [Contact](#contact)
- [Statut du projet](#statut-du-projet)
- [Structure du dépôt](#structure-du-dépôt)
- [Équipe de développement](#équipe-de-développement)
- [Stack technologique](#stack-technologique)

## À propos

LEVELOP est une plateforme communautaire gaming qui résout la fragmentation des services pour joueurs en offrant un espace unifié où les gamers peuvent :
- Acheter des produits numériques gaming
- Participer à des discussions communautaires
- Prendre part à des événements gaming
- Recevoir du coaching professionnel
- Suivre leur progression
- Profiter d'une assistance gaming basée sur l'IA
- Estimer les performances de jeu avant achat

L'objectif est de créer une plateforme tout-en-un qui enrichit l'expérience gaming tout en offrant des services utiles, des opportunités d'engagement communautaire et une assistance intelligente.

## Fonctionnalités

### Boutique e-commerce
- Catalogue de produits numériques avec fiches détaillées
- Paiement sécurisé via Stripe
- Support multi-devises (TND/EUR)
- Estimation FPS basée sur l'IA selon la configuration utilisateur
- Avis et notes sur les produits
- Gestion des stocks
- Vérification de compatibilité des performances

### Assistant Gaming IA
- Chatbot gaming disponible 24/7
- Recommandations de jeux selon les préférences
- Conseils de compatibilité matérielle
- Astuces d'optimisation des performances
- Dépannage spécifique aux jeux
- Assistance communautaire
- Support gaming en temps réel

### Forum
- Discussions par sujet avec support média
- Intégration Reddit en temps réel pour les sujets tendances
- Analyse de sentiment des messages
- Partage d'images et de vidéos
- Système de commentaires et réactions
- Recommandations de sujets

### Gestion d'événements
- Création et gestion d'événements
- Organisation par catégorie
- Intégration calendrier
- Gestion des capacités
- Galerie photo d'événements
- Filtrage par localisation
- Notifications email pour les mises à jour d'événements

### Coaching
- Profils coachs et gestion des disponibilités
- Système de réservation de sessions
- Sessions promotionnelles
- Mise en relation coach-client
- Planification des sessions
- Paiement intégré

## Installation

```bash
# Cloner le dépôt
git clone https://github.com/feresad/PidevSymfony.git

# Aller dans le dossier du projet
cd PidevSymfony

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local
# Modifier .env.local avec votre configuration

# Créer la base de données
php bin/console doctrine:database:create

# Lancer les migrations
php bin/console doctrine:migrations:migrate

# Démarrer le serveur de développement
symfony server:start
```

## Utilisation

### Rôles Utilisateurs

#### Client
- Parcourir et acheter des produits
- Participer aux discussions du forum
- S'inscrire à des événements
- Réserver des sessions de coaching
- Gérer son profil et ses commandes
- Utiliser l'assistant gaming IA
- Vérifier les estimations FPS

#### Coach
- Gérer ses disponibilités
- Créer et gérer des sessions
- Voir les réservations clients
- Suivre ses gains
- Mettre à jour son profil

#### Admin
- Gérer le contenu de la plateforme
- Modérer les forums
- Gérer les événements
- Traiter les candidatures coach
- Générer des rapports

### Utilisation des principales fonctionnalités

#### Boutique
1. Parcourir les produits
2. Vérifier la compatibilité
3. Obtenir une estimation FPS IA
4. Ajouter au panier
5. Finaliser le paiement
6. Accéder aux articles achetés

#### Assistant Gaming IA
1. Accéder au chatbot depuis n'importe quelle page
2. Poser des questions liées au gaming
3. Obtenir des recommandations matérielles
4. Recevoir des conseils de performance
5. Dépanner des problèmes gaming
6. Obtenir des conseils spécifiques à un jeu

#### Forum
1. Créer des sujets
2. Partager des médias
3. Participer aux discussions
4. Réagir aux messages
5. Suivre les sujets tendances

#### Événements
1. Parcourir les événements
2. Vérifier les disponibilités
3. S'inscrire
4. Recevoir des notifications
5. Participer

#### Coaching
1. Trouver un coach
2. Vérifier les disponibilités
3. Réserver une session
4. Effectuer le paiement
5. Recevoir le coaching

### Fonctionnalité d'estimation FPS
1. Sélectionner un jeu/produit
2. Saisir votre configuration :
   - Modèle de CPU
   - Modèle de GPU
   - Quantité de RAM
   - Type de stockage
3. Obtenir une estimation FPS IA pour :
   - Paramètres minimum
   - Paramètres recommandés
   - Paramètres maximum
4. Comparer avec des systèmes similaires
5. Recevoir des recommandations d'optimisation

## Contribuer

1. Forker le dépôt
2. Créer une branche de fonctionnalité
3. Apporter vos modifications
4. Soumettre une pull request

### Bonnes pratiques de développement
- Suivre les standards PSR-12
- Écrire des tests unitaires pour les nouvelles fonctionnalités
- Mettre à jour la documentation
- Maintenir la rétrocompatibilité

## Licence

Propriétaire - Tous droits réservés

## Contact

- Email : [levelopcorporation@gmail.com](mailto:levelopcorporation@gmail.com)
- GitHub : [github.com/feresad/PidevSymfony](https://github.com/feresad/PidevSymfony)

## Statut du projet

Ce projet est actuellement en développement et n'est pas encore hébergé. Le dépôt est disponible sur GitHub pour la collaboration et la contribution.

## Structure du dépôt

```
PidevSymfony/
├── assets/          # Assets frontend
├── bin/            # Fichiers exécutables
├── config/         # Fichiers de configuration
├── migrations/     # Migrations de base de données
├── public/         # Dossier public
├── src/            # Code source
├── templates/      # Templates Twig
├── tests/          # Fichiers de test
├── translations/   # Fichiers de traduction
└── uploads/        # Dossier d'uploads
```

## Équipe de développement

- [@feresad](https://github.com/feresad)
- [@rayN3A7](https://github.com/rayN3A7)
- [@hsounaSellami](https://github.com/hsounaSellami)
- [@H1000Rekik](https://github.com/H1000Rekik)
- [@hazemmtir0](https://github.com/hazemmtir0)

## Stack technologique

- Twig (51.1%)
- PHP (26.3%)
- CSS (15.2%)
- JavaScript (7.4%) 
