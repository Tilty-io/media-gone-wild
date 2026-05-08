Rédige TOUJOURS les messages de commit en français, sans aucune exception.

Pour les messages de commit, suis les instructions définies dans #file:git-commit-instructions.md

# Instructions GitHub Copilot

## Langue et accents
Respecte toujours les accents en français dans les exemples et dans les commentaires de code. N'écris pas de texte français sans accents.

## Textes visibles par l'utilisateur
Dans les textes visibles par l'utilisateur final (pages, interfaces, messages, libellés, documentation publique), ne mentionne JAMAIS les prompts internes, les demandes de l'utilisateur, la conversation en cours, ni les sources d'inspiration utilisées pour concevoir une interface ou une fonctionnalité.
Le résultat final doit toujours paraître natif au produit, sans exposer le contexte de travail, les comparaisons externes ni les coulisses de conception.





## Rétrocompatibilité
Ne perds pas de temps à essayer de maintenir la compatibilité avec les anciennes versions, on s'en fout.
Ne documente pas la rétrocompatibilité... on s'en fout.
Ne parle pas des versions précédentes... on s'en fout.
On se fout de la rétrocompatibilité du projet. Si une modification casse la compatibilité avec les versions
précédentes, c'est pas grave, on s'en fout. On veut juste que le projet avance et que les nouvelles fonctionnalités 
soient implémentées rapidement. 

## PHP DOC
Rédige toujours les PHP DOC en français, même si le code est en anglais. Les commentaires et les descriptions doivent être en français pour faciliter la compréhension des développeurs francophones.
Commente TOUJOURS les fonctions, les classes et les méthodes en français, en expliquant clairement leur rôle et leur fonctionnement. Utilise des phrases complètes et évite les abréviations pour que les commentaires soient clairs et compréhensibles pour tous les développeurs, même ceux qui ne sont pas familiers avec le projet.

## Nouvelles fonctionnalités
À chaque fois qu'une nouvelle fonctionnalité est ajoutée, tu dois systématiquement vérifier si elle doit être documentée et si des tests doivent être ajoutés ou mis à jour.

### Documentation
Quand une fonctionnalité ajoute un nouvel endpoint, une nouvelle page, un nouveau paramètre, un nouveau comportement métier ou une nouvelle contrainte d'usage, mets à jour la documentation correspondante.

### Tests
Quand une fonctionnalité ajoute ou modifie une logique métier, ajoute des tests unitaires ou mets à jour les tests existants. Si la fonctionnalité change le comportement attendu, les tests doivent refléter ce nouveau comportement.

### Fichiers à vérifier et à mettre à jour si nécessaire
- `README.md`
- `docs/explain-media-gone-wild-to-agents.md`
- `app/Config/Routes.php`
- `app/Controllers/`
- `app/Services/`
- `app/DTO/`
- `src/`
- `app/Views/`
- `tests/Unit/`
- `.gitignore`

## gitignore
Au fur et à mesure que tu ajoutes des fichiers au projet, n'oublie pas de mettre à jour le fichier `.gitignore` pour éviter de committer des fichiers inutiles ou sensibles. Par exemple, si tu ajoutes un fichier de configuration qui contient des informations sensibles, assure-toi de l'ajouter au `.gitignore` pour qu'il ne soit pas committé par erreur.


