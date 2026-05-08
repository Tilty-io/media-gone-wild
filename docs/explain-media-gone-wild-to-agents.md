# explain-media-gone-wild-to-agents

```text
Tu utilises l'API media-gone-wild.

Base URL fixe :
https://media-gone-wild.tilty.io/

Endpoints disponibles :
- GET https://media-gone-wild.tilty.io/
- GET https://media-gone-wild.tilty.io/photo
- GET https://media-gone-wild.tilty.io/photo.jpg
- GET https://media-gone-wild.tilty.io/photo.png
- GET https://media-gone-wild.tilty.io/photo.webp
- GET https://media-gone-wild.tilty.io/photo.gif
- GET https://media-gone-wild.tilty.io/video
- GET https://media-gone-wild.tilty.io/logo

Ce que signifient les endpoints :
- `/photo` = une photo aléatoire issue de la collection photo du projet
- `/photo.jpg` = une photo aléatoire convertie en JPEG (équivalent à `/photo?extension=jpg`)
- `/photo.png` = une photo aléatoire convertie en PNG
- `/photo.webp` = une photo aléatoire convertie en WebP
- `/photo.gif` = une photo aléatoire convertie en GIF
- `/video` = une vidéo aléatoire issue de la collection vidéo du projet
- `/logo` = un logo aléatoire issu de la collection de logos du projet
- les mêmes endpoints acceptent aussi `?id=...` pour cibler un média exact

Formats actuellement renvoyés :
- `/photo` renvoie actuellement un fichier image JPEG par défaut, mais peut aussi renvoyer `jpg`, `png`, `webp` ou `gif` si le paramètre `extension` est utilisé ou si l'extension est précisée dans l'URL
- `/video` renvoie actuellement un fichier vidéo MP4
- `/logo` renvoie actuellement un fichier image SVG

Paramètres supportés :
- seed
- id
- width (photo uniquement)
- height (photo uniquement)
- fit (photo uniquement) = `contain`, `cover`, `fill`, `scale`
- extension (photo uniquement) = `jpg`, `png`, `webp`, `gif`
- quality (photo uniquement) = `1` à `100`
- bgcolor (photo uniquement) = hex 6 chars, hex 8 chars `RRGGBBAA` ou `transparent`

Règles pour l'extension de format photo :
- L'extension peut être précisée dans l'URL : `/photo.webp?seed=robert` ou via le paramètre query : `/photo?extension=webp&seed=robert`.
- L'extension de l'URL est prioritaire sur le paramètre `?extension=` si les deux sont présents.
- L'extension de l'URL et le paramètre `extension` sont strictement réservés aux photos. Pour `/video` et `/logo`, ces informations sont ignorées silencieusement.

Note technique — logos SVG :
- Les logos sont des fichiers SVG. La transformation raster (conversion PNG, JPG, redimensionnement…) n'est pas supportée.
- Le driver GD (PHP) ne sait pas décoder les SVG. Avec Imagick + librsvg ce serait possible, mais ces dépendances système ne sont pas disponibles.
- `/logo.png`, `/logo.jpg`, etc. servent le SVG original sans transformation.

Exemples :
- https://media-gone-wild.tilty.io/photo
- https://media-gone-wild.tilty.io/photo.jpg?id=abc123def456
- https://media-gone-wild.tilty.io/photo.webp?seed=robert&width=800&height=600&fit=cover
- https://media-gone-wild.tilty.io/photo.png?seed=demo&width=200&height=200&fit=contain&bgcolor=ffffff00
- https://media-gone-wild.tilty.io/photo?id=abc123def456
- https://media-gone-wild.tilty.io/photo?seed=robert
- https://media-gone-wild.tilty.io/photo?seed=robert&width=800&height=600&fit=cover
- https://media-gone-wild.tilty.io/photo?id=abc123def456&width=300&extension=webp&quality=90
- https://media-gone-wild.tilty.io/photo?seed=demo&width=200&height=200&fit=contain&bgcolor=ffffff00&extension=png
- https://media-gone-wild.tilty.io/video
- https://media-gone-wild.tilty.io/video?id=def456abc123
- https://media-gone-wild.tilty.io/video?seed=michel
- https://media-gone-wild.tilty.io/logo
- https://media-gone-wild.tilty.io/logo?id=789abc123def
- https://media-gone-wild.tilty.io/logo?seed=demo

Règles :
- Utilise uniquement ces endpoints.
- Utilise `id` quand tu dois récupérer exactement le même média.
- Utilise `seed` quand tu veux un résultat stable mais pseudo-aléatoire.
- `1 id = 1 média`.
- Si `id` est fourni, il est prioritaire sur `seed`.
- Les paramètres `width`, `height`, `fit`, `extension`, `quality` et `bgcolor` ne s'appliquent qu'à `/photo`.
- Pour `/video` et `/logo`, ignore ces paramètres de transformation.
- `bgcolor=transparent` est un alias de `00000000`.
- Pour un hex 8 caractères, les 2 derniers représentent l'alpha : `ffffff00` = transparent, `ffffff80` = semi-transparent.
- N'invente pas d'autres paramètres.
- `/photo` renvoie une photo, pas une illustration arbitraire.
- `/video` renvoie une vraie vidéo binaire.
- `/logo` renvoie un logo, généralement au format SVG.
- `/` renvoie une page HTML d'accueil qui explique l'usage de l'API.
- La home inclut un sélecteur d'ID photo commun à toutes les démos, une carte interactive pour `fit` (mode, proportions `4/3` `3/4` `16/9` `1/1`, fond noir/blanc/jaune semi-transparent, légende `taille réelle`), et des cartes URL avec actions copier + ouverture dans la popin de transformation.
```


