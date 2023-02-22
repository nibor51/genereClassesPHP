# Générateur de classes à partir de tables de base de données

Ce script permet de générer des classes PHP à partir de tables de base de données.
Il est actuellement en version alpha, et peut donc contenir des bugs.

## Installation
    
Pour utiliser ce générateur de classes, vous devez :

    1. Copier les fichiers dans le répertoire de votre projet
    2. Créer et configurer le fichier _connec.php en fonction de votre base de données
    3. Exécuter le fichier index.php via la console avec la commande `php app/index.php`

## Utilisation

Une fois le script exécuté, il va générer un fichier par table de la base de données, dans le répertoire `src/Model/`.
Vous pouvez ensuite inclure ces fichiers dans vos scripts PHP, et utiliser les classes générées.
Pour tester les classes générées, vous pouvez utiliser le fichier `app/test.php` qui se trouve à la racine du projet.

 ## Avertissement

Ce script est fourni tel quel, sans aucune garantie. Il est possible qu'il contienne des bugs, ou qu'il ne fonctionne pas avec votre base de données. Il est donc fortement conseillé de vérifier le code généré avant de l'utiliser.

## Licence

Ce script est sous licence MIT. Vous pouvez donc l'utiliser comme bon vous semble, mais à vos risques et périls.

## TODO

- [x] fix les types de données d'entrée et de sortie des méthodes
- [X] optimiser la génération des méthodes de la classe (trop de code répété)
- [ ] ajouter des commentaires dans le code source
- [ ] ajouter des commentaires dans les classes générées
- [ ] ajouter des tests automatiques
