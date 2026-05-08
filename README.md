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

## IDs

Chaque média dispose d'un identifiant stable, unique, sans extension, composé uniquement de caractères `a-z` et `0-9`.

Contrairement au `seed`, un `id` désigne exactement un seul média : `1 id = 1 média`.

Les IDs sont stockés dans `media/ids.json`.

Quand vous ajoutez de nouveaux médias, synchronisez les IDs manquants avec :

`php spark media:sync-ids`

Mode simulation (sans écriture) :

`php spark media:sync-ids --dry-run`

En environnement `development`, le catalogue tente une synchronisation automatique si des IDs sont manquants.
La synchronisation automatique est aussi autorisée depuis `localhost`/`127.0.0.1`/`::1` (même si l'environnement n'est pas `development`).
En production distante, le catalogue affiche une alerte et n'écrit rien automatiquement.

En plus, en `development` ou en local (`localhost`/`127.0.0.1`/`::1`), si une requête `?id=...` ne trouve pas le média,
l'API tente une synchronisation automatique (`media:sync-ids`) puis retente la résolution de cet ID une fois.

#### Une `photo` avec un ID connu

`https://media-gone-wild.tilty.io/photo?id=abc123def456`

#### Une `video` avec un ID connu

`https://media-gone-wild.tilty.io/video?id=def456abc123`

#### Un `logo` avec un ID connu

`https://media-gone-wild.tilty.io/logo?id=789abc123def`

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

Les paramètres sont passés via query string. Utilisez `id` pour viser un média exact et `seed` pour un résultat reproductible mais pseudo-aléatoire.

Si `id` et `seed` sont fournis en même temps, `id` est prioritaire.

### Paramètres de sélection

| Paramètre | Type   | Description |
|-----------|--------|-------------|
| `id`      | string | Cible un média exact par son identifiant stable |
| `seed`    | string | Résultat pseudo-aléatoire mais reproductible |

### Paramètres de transformation image (photo uniquement)

Ces paramètres s'appliquent uniquement à l'endpoint `/photo`. Ils sont ignorés silencieusement pour `/video` et `/logo`.

| Paramètre   | Type   | Valeurs acceptées                           | Défaut  |
|-------------|--------|---------------------------------------------|---------|
| `width`     | int    | ≥ 1 (pixels)                               | —       |
| `height`    | int    | ≥ 1 (pixels)                               | —       |
| `fit`       | string | `contain`, `cover`, `fill`, `scale` | `contain` |
| `extension` | string | `jpg`, `jpeg`, `png`, `webp`, `gif`         | —       |
| `quality`   | int    | 1–100                                       | `85`    |
| `bgcolor`   | string | hex 6 chars (`ffffff`), hex 8 chars RRGGBBAA (`ffffff80`), ou `transparent` | — |

#### Comportement des modes `fit`

- **`contain`** — redimensionne en conservant le ratio ; le letterbox visible dépend du canvas cible
- **`cover`** — remplit tout le canvas en recadrant le surplus (recadrage centré)
- **`fill`** — étire l'image aux dimensions exactes sans conserver le ratio
- **`scale`** — redimensionne proportionnellement (peut ne pas remplir tout le canvas)

#### Règles de transparence (`bgcolor`)

La transparence est portée par les 2 derniers caractères hex (`AA`) dans un bgcolor 8 chars :
- `ffffffff` → blanc opaque
- `ffffff80` → blanc semi-transparent (alpha = 128/255)
- `ffffff00` → blanc transparent (alpha = 0)
- `transparent` → alias de `00000000`

Si `bgcolor` implique une transparence, utilisez `png` ou `webp` comme `extension` pour la conserver.
Quand `bgcolor` est fourni, l'API remplit d'abord un fond avec cette couleur puis compose l'image transformée par-dessus,
ce qui applique aussi la couleur aux zones transparentes internes de l'image source.

Pour `jpg`, la transparence ne peut pas être conservée : le rendu final est aplati.

#### Images transformées et cache

Les images transformées sont automatiquement mises en cache dans `writable/cache/transformed/`.
Si la même combinaison `id + options` est demandée à nouveau, le fichier de cache est renvoyé directement.

Les fichiers de cache sont organisés par version de pipeline puis par ID média :

```
writable/cache/transformed/{version}/{id}/{hash}.{ext}
```

Le cache inclut aussi une version interne de pipeline (`TRANSFORM_CACHE_VERSION` dans `app/Services/MediaService.php`).
Incrémentez cette version à chaque modification du code qui change le rendu final des images,
afin de forcer la génération d'un nouveau fichier de cache même avec les mêmes paramètres.
Les anciens dossiers de version peuvent être supprimés manuellement sans risque.

#### Exemples

`https://media-gone-wild.tilty.io/photo?id=abc123def456`

`https://media-gone-wild.tilty.io/photo?seed=michel&width=800&height=600&fit=cover`

`https://media-gone-wild.tilty.io/photo?id=abc123def456&width=400&extension=webp&quality=90`

`https://media-gone-wild.tilty.io/photo?seed=demo&width=200&height=200&fit=contain&bgcolor=ffffff`

`https://media-gone-wild.tilty.io/photo?seed=demo&width=200&height=200&fit=contain&bgcolor=ffffff00&extension=png`

## Catalogue

Une page catalogue permet de parcourir les médias disponibles avec un filtre par type. Chaque carte affiche l'identifiant stable du média et permet de l'ouvrir directement via `?id=`.

Pour les photos, le catalogue propose aussi :

- des exemples directs de transformations prêtes à ouvrir ;
- un générateur d'URL en popin qui s'ouvre depuis une carte photo (ou son bouton dédié) et compose automatiquement une URL `/photo?id=...` avec `width`, `height`, `fit`, `extension`, `quality` et `bgcolor`.

Quand tu ouvres le générateur depuis une autre photo, les paramètres saisis sont conservés et seul l'ID est remplacé.

`https://media-gone-wild.tilty.io/catalogue`

Exemple avec filtre et limite :

`https://media-gone-wild.tilty.io/catalogue?type=photo&limit=50`

Exemple avec pagination :

`https://media-gone-wild.tilty.io/catalogue?type=photo&limit=50&page=2`

Le catalogue inclut aussi un bouton **Mélanger** (anciennement Random) qui randomise l'ordre d'affichage des médias selon le filtre courant.

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
