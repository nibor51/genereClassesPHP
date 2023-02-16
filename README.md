# Générateur de classes à partir de tables de base de données

Ce script permet de générer des classes PHP à partir de tables de base de données.
Il est actuellement en version alpha, et n'est pas encore très complet.

## Installation
    
Pour utiliser ce générateur de classes, vous devez :

    1. Copier les fichiers dans le répertoire de votre projet
    2. Modifier le fichier `config.php` pour y renseigner les informations de connexion à votre base de données
    3. Exécuter le fichier `generate.php`

## Utilisation

 Une fois le fichier `generate.php` exécuté, il va générer les classes dans le répertoire `classes` du projet.

 ## Avertissement

Ce script est fourni tel quel, sans aucune garantie. Il est possible qu'il contienne des bugs, ou qu'il ne fonctionne pas avec votre base de données. Il est donc fortement conseillé de vérifier le code généré avant de l'utiliser.

## Licence

Ce script est sous licence MIT. Vous pouvez donc l'utiliser comme bon vous semble, mais à vos risques et périls.

## TODO

- [ ] Ajouter un système de gestion des erreurs PHP sur la génération des classes
- [ ] Générer les méthodes de validation des données avant insertion dans la base de données (avec gestion des erreurs)
- [ ] Générer les méthodes de relations entre les tables
- [ ] Générer les méthodes de conversion des données (par exemple, pour convertir une date au format `Y-m-d` en objet `DateTime`)
- [ ] Création d'un script permettant de générer les classes depuis la console (avec gestion des erreurs)
- [ ] convertir le script PHP en classe PHP