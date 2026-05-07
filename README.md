# media-gone-wild
Collection de photos, illustrations, logos et vidéos aléatoires + son API


# API

## Endpoints aléatoires

Les endpoints aléatoires permettent d'obtenir un résultat différent à chaque requête, sans possibilité de reproduction.
Attention cependant, votre navigateur ou votre cache peut parfois réutiliser une image déjà obtenue, donnant
l'impression que le résultat n'est pas aléatoire. Pour éviter cela, vous pouvez ajouter un paramètre de cache-busting à l'URL, comme `?r=12345` (le nombre peut être n'importe quoi).


#### Une vidéo aléatoire

`https://media-gone-wild.tilty.io/video`

ou

`https://media-gone-wild.tilty.io/video?r=12345`


#### Une photo aléatoire

`https://media-gone-wild.tilty.io/photo`

ou

`https://media-gone-wild.tilty.io/photo?r=12345`


#### Un logo aléatoire

`https://media-gone-wild.tilty.io/logo`

ou

`https://media-gone-wild.tilty.io/logo?r=12345`


## Seeds

Les seeds permettent d'obtenir des résultats aléatoires mais reproductibles. En utilisant le même seed, vous obtiendrez toujours le même résultat.

#### Une `video` avec un seed spécifique

`https://media-gone-wild.tilty.io/video?seed=robert`

`https://media-gone-wild.tilty.io/video?seed=michel`

#### Une `photo` avec un seed spécifique

`https://media-gone-wild.tilty.io/photo?seed=robert`

`https://media-gone-wild.tilty.io/photo?seed=michel`

#### Un `logo` avec un seed spécifique

`https://media-gone-wild.tilty.io/logo?seed=robert`

`https://media-gone-wild.tilty.io/logo?seed=michel`

## Paramètres

Les paramètres sont passés via query string. Vous pouvez combiner `seed` avec des paramètres de transformation.

#### Exemples

`https://media-gone-wild.tilty.io/photo?seed=robert&width=50&height=50&resize=cover`

`https://media-gone-wild.tilty.io/photo?seed=michel&blur=8&saturation=120`

## Catalogue

Une page catalogue permet de parcourir les médias disponibles avec un filtre par type.

`https://media-gone-wild.tilty.io/catalogue`

Exemple avec filtre et limite :

`https://media-gone-wild.tilty.io/catalogue?type=photo&limit=500`

Exemple avec pagination :

`https://media-gone-wild.tilty.io/catalogue?type=photo&limit=500&page=2`

## Tests unitaires

Installez les dépendances de développement puis lancez la suite de tests.

`composer install`

`composer test`

## Déploiement / synchro serveur

La documentation de synchronisation vers un serveur mutualisé est disponible ici :

`docs/synchro-serveur-mutualise.md`

## Prompt système pour agents

Un prompt système prêt à copier pour expliquer l'usage réel actuel de l'API à des agents est disponible ici :

`docs/explain-media-gone-wild-to-agents.md`

