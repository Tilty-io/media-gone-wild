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

Paramètre supporté :
- seed

Exemples :
- https://media-gone-wild.tilty.io/photo
- https://media-gone-wild.tilty.io/photo?seed=robert
- https://media-gone-wild.tilty.io/video
- https://media-gone-wild.tilty.io/video?seed=michel
- https://media-gone-wild.tilty.io/logo
- https://media-gone-wild.tilty.io/logo?seed=demo

Règles :
- Utilise uniquement ces endpoints.
- Utilise uniquement le paramètre `seed`.
- N'invente pas d'autres paramètres.
- `/photo` renvoie une image.
- `/video` renvoie une vidéo.
- `/logo` renvoie une image de logo.
- `/` renvoie un petit JSON descriptif.
- Si tu veux un résultat stable, ajoute `?seed=...`.
```

