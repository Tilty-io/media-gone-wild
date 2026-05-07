# explain-media-gone-wild-to-agents

```text
Tu utilises l'API media-gone-wild.

Base URL fixe :
https://media-gone-wild.tilty.io/

Endpoints disponibles :
- GET https://media-gone-wild.tilty.io/
- GET https://media-gone-wild.tilty.io/photo
- GET https://media-gone-wild.tilty.io/video
- GET https://media-gone-wild.tilty.io/logo

Ce que signifient les endpoints :
- `/photo` = une photo aléatoire issue de la collection photo du projet
- `/video` = une vidéo aléatoire issue de la collection vidéo du projet
- `/logo` = un logo aléatoire issu de la collection de logos du projet
- les mêmes endpoints acceptent aussi `?id=...` pour cibler un média exact

Formats actuellement renvoyés :
- `/photo` renvoie actuellement un fichier image JPEG par défaut, mais peut aussi renvoyer `jpg`, `png`, `webp` ou `gif` si le paramètre `extension` est utilisé
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

Exemples :
- https://media-gone-wild.tilty.io/photo
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
```


