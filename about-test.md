# À propos des tests

Ce document explique **les tests déjà en place dans le projet** et le vocabulaire de base pour comprendre à quoi ils servent.

L'idée n'est pas de faire un cours académique, mais de te donner une base solide pour lire, lancer et écrire des tests sans être perdu.

## Checklist de lecture

- comprendre ce qu'est un test unitaire ;
- voir quels outils sont installés dans le projet ;
- comprendre comment PHPUnit découvre et lance les tests ;
- lire les deux fichiers de tests existants sans stress ;
- apprendre les assertions principales déjà utilisées ;
- savoir quoi tester ensuite si tu veux aller plus loin.

## 1. C'est quoi un test unitaire ?

Un **test unitaire** vérifie qu'un petit morceau de code fait bien ce qu'on attend de lui.

Dans ce projet, un test unitaire vérifie par exemple :

- que `Router` reconnaît bien `/photo`, `/video` et `/logo` ;
- que `MediaRepository` renvoie `null` si un dossier n'existe pas ;
- que `MediaRepository` renvoie toujours le même fichier si on utilise le même `seed`.

L'idée importante :

- on teste **une unité de code** ;
- on vérifie **un comportement précis** ;
- on automatise la vérification pour éviter de refaire ça à la main à chaque modification.

En pratique, les tests servent à deux choses :

1. **éviter les régressions** : on casse moins facilement un comportement qui marchait ;
2. **documenter le code** : un bon test montre ce que la classe est censée faire.

## 2. L'outil utilisé ici : PHPUnit

Le projet utilise **PHPUnit**, qui est l'outil de test le plus classique en PHP.

On le voit dans `composer.json` :

- `phpunit/phpunit` est présent dans `require-dev` ;
- le script Composer `test` lance `phpunit` ;
- `autoload-dev` permet à Composer de charger proprement les classes de test.

Concrètement, ça veut dire que tu peux lancer les tests avec :

```powershell
php composer.phar test
```

Tu peux aussi lancer PHPUnit directement si besoin :

```powershell
vendor\bin\phpunit
```

## 3. Comment PHPUnit sait quoi lancer ?

C'est le rôle de `phpunit.xml.dist`.

Dans ce fichier :

- `bootstrap="vendor/autoload.php"` dit à PHPUnit de charger l'autoloader Composer avant de lancer les tests ;
- la suite de tests `Unit` pointe vers le dossier `tests/Unit`.

Autrement dit, quand tu lances les tests :

1. PHPUnit démarre ;
2. il charge `vendor/autoload.php` ;
3. il cherche les classes de test dans `tests/Unit` ;
4. il exécute chaque méthode de test ;
5. il t'affiche un résultat global.

## 4. Structure actuelle des tests du projet

Aujourd'hui, les tests sont ici :

- `tests/Unit/RouterTest.php`
- `tests/Unit/MediaRepositoryTest.php`

Et ils testent ces classes de production :

- `src/Router.php`
- `src/MediaRepository.php`

C'est une bonne base, car ce sont deux morceaux de logique simples et importants.

## 5. Comment lire un test sans paniquer

Un test suit souvent cette structure mentale :

1. **préparer** le contexte ;
2. **exécuter** le code à tester ;
3. **vérifier** le résultat.

On appelle souvent ça :

- **Arrange** : je prépare ;
- **Act** : j'exécute ;
- **Assert** : je vérifie.

Exemple très simple :

- je crée un `Router` ;
- je lui donne `/photo` ;
- je vérifie qu'il retourne `photo`.

## 6. Explication de `tests/Unit/RouterTest.php`

Ce fichier teste la classe `Router`.

Son rôle dans l'application est simple :

- recevoir un chemin d'URL ;
- déterminer si ce chemin correspond à un type de média autorisé.

### Test 1 : `testResolveMediaTypeReturnsExpectedTypeForValidPath`

Ce test vérifie que les chemins valides sont bien reconnus.

Il teste notamment :

- `/photo`
- `video`
- `/logo/`

Pourquoi ces variantes ?

Parce qu'on veut vérifier que le routeur tolère les slashs courants en entrée, tant que le chemin correspond bien à un endpoint autorisé.

Les assertions utilisées sont :

- `assertSame('photo', ...)`
- `assertSame('video', ...)`
- `assertSame('logo', ...)`

### Que veut dire `assertSame` ?

`assertSame` vérifie que la valeur retournée est **exactement** celle attendue.

Ici, cela veut dire par exemple :

- on attend strictement la chaîne `photo` ;
- pas `Photo` ;
- pas `true` ;
- pas une autre valeur équivalente.

### Test 2 : `testResolveMediaTypeReturnsNullForInvalidPath`

Ce test vérifie que les chemins invalides sont rejetés.

Il teste par exemple :

- `/`
- `/unknown`
- `/photo/test`

L'idée est importante :

- le routeur ne doit pas être trop permissif ;
- il doit refuser les chemins qui ne correspondent pas exactement au contrat de l'API.

L'assertion utilisée ici est `assertNull(...)`.

### Que veut dire `assertNull` ?

Cela veut dire :

- on s'attend à ce que la méthode retourne `null` ;
- donc à ce qu'elle dise en quelque sorte : « je ne reconnais pas cette route ».

## 7. Explication de `tests/Unit/MediaRepositoryTest.php`

Ce fichier teste la classe `MediaRepository`.

Son rôle est de :

- regarder dans un dossier de médias ;
- filtrer les vrais fichiers ;
- en choisir un ;
- éventuellement faire un choix déterministe avec un `seed`.

C'est déjà un peu plus concret que `Router`, donc ce fichier est très intéressant pour apprendre.

### Test 1 : `testPickReturnsNullWhenDirectoryDoesNotExist`

Ce test vérifie le cas où le dossier demandé n'existe pas.

Exemple mental :

- on demande un média de type `photo` ;
- mais le dossier `photo` n'existe pas à l'endroit indiqué ;
- la méthode doit retourner `null`.

Ce test vérifie que le code gère proprement ce cas au lieu de planter.

### Test 2 : `testPickReturnsNullWhenDirectoryIsEmpty`

Ici, le dossier existe, mais il ne contient aucun fichier.

Le comportement attendu est encore `null`.

C'est important, car :

- un dossier présent ne garantit pas qu'il y a un média exploitable ;
- le code doit distinguer « dossier trouvé » et « contenu utilisable ».

### Test 3 : `testPickWithSeedIsDeterministic`

C'est un test très utile pour comprendre la notion de **déterminisme**.

Dans ce test :

- on crée un dossier temporaire ;
- on y met trois faux fichiers : `a.jpg`, `b.jpg`, `c.jpg` ;
- on appelle deux fois `pick('photo', 'robert')`.

Ce qu'on vérifie :

- avec le même `seed`, on doit retomber sur le même fichier.

Autrement dit, le hasard est **maîtrisé**.

Ce n'est plus un hasard pur :

- même entrée ;
- même résultat.

C'est très utile pour ton API, parce qu'un utilisateur peut refaire la même requête avec le même `seed` et retrouver le même média.

### Test 4 : `testPickWithoutSeedReturnsAnExistingFile`

Ce test couvre le cas inverse.

Ici, il n'y a **pas** de `seed`.

Donc on ne cherche pas à prouver quel fichier précis sera choisi, car la méthode utilise `random_int(...)`.

À la place, on vérifie quelque chose de plus intelligent :

- la méthode retourne bien un chemin ;
- ce chemin correspond bien à un fichier qui existe.

C'est une très bonne habitude de test :

- ne pas tester plus que nécessaire ;
- vérifier le contrat réel de la méthode.

## 8. Le rôle du répertoire temporaire dans les tests

Dans `MediaRepositoryTest`, il y a une méthode utilitaire :

- `createTemporaryDirectory()`

Elle sert à créer un dossier temporaire pour chaque scénario.

Pourquoi c'est bien ?

Parce que le test :

- ne dépend pas des vrais fichiers du projet ;
- ne modifie pas les vrais médias ;
- reste isolé ;
- nettoie ce qu'il crée à la fin.

C'est un point central en tests unitaires :

> un test doit être autonome et ne pas salir l'environnement.

## 9. Les assertions déjà utilisées dans le projet

Voici les principales assertions déjà présentes.

### `assertSame($attendu, $réel)`

Vérifie que la valeur réelle est strictement identique à la valeur attendue.

Exemples :

- `photo` doit être `photo` ;
- le chemin retourné avec le même `seed` doit être exactement le même.

### `assertNull($valeur)`

Vérifie qu'une valeur est `null`.

Exemple :

- si un dossier n'existe pas, `pick(...)` doit retourner `null`.

### `assertNotNull($valeur)`

Vérifie qu'on a bien reçu quelque chose.

Exemple :

- avec un `seed` valide et des fichiers présents, la méthode doit retourner un chemin.

### `assertFileExists($chemin)`

Vérifie qu'un chemin pointe bien vers un fichier existant sur le disque.

Exemple :

- si `pick('video')` retourne un chemin, ce chemin doit correspondre à un vrai fichier.

## 10. Pourquoi ces tests sont utiles pour ce projet

Ils protègent deux comportements importants :

### Le routage

Si quelqu'un modifie `Router`, les tests diront rapidement si :

- `/photo` n'est plus reconnu ;
- une route invalide devient acceptée par erreur.

### La sélection des médias

Si quelqu'un modifie `MediaRepository`, les tests diront rapidement si :

- un dossier absent provoque un comportement incorrect ;
- la logique du `seed` n'est plus stable ;
- la sélection aléatoire ne renvoie plus un vrai fichier.

## 11. Ce que ces tests ne couvrent pas encore

C'est important de comprendre qu'une suite de tests n'est jamais « finie ».

Pour l'instant, ces tests **ne vérifient pas directement** :

- les réponses HTTP de `index.php` ;
- les headers `Content-Type` ou `Content-Disposition` ;
- le comportement Apache local ;
- les permissions Windows sur les fichiers médias ;
- l'affichage réel dans le navigateur.

Ces sujets relèvent plutôt de :

- tests d'intégration ;
- tests fonctionnels ;
- tests manuels.

Donc :

- les tests actuels sont utiles ;
- mais ils ne remplacent pas tous les autres types de vérification.

## 12. Comment lancer les tests toi-même

Depuis la racine du projet :

```powershell
php composer.phar test
```

Si tu veux lancer PHPUnit directement :

```powershell
vendor\bin\phpunit
```

Si tu veux une sortie plus verbeuse plus tard, tu pourras aussi utiliser des options PHPUnit, mais la commande actuelle suffit largement pour débuter.

## 13. Comment lire le résultat

Quand tout va bien, tu verras quelque chose de ce genre :

- un point par test réussi ;
- un résumé final avec le nombre de tests et d'assertions.

Exemple :

- `OK (6 tests, 15 assertions)`

Cela veut dire :

- 6 méthodes de test ont été exécutées ;
- 15 vérifications ont été faites au total.

## 14. Petit lexique ultra simple

### Test unitaire

Test d'un petit morceau de code isolé.

### Assertion

Vérification faite dans un test.

### Seed

Valeur servant à rendre un résultat pseudo-aléatoire mais reproductible.

### Déterministe

Qui donne le même résultat quand on repart des mêmes entrées.

### Fixture

Données utilisées par un test.

Dans ton projet, les petits fichiers temporaires créés dans `MediaRepositoryTest` jouent ce rôle.

### Régression

Bug introduit dans un comportement qui marchait avant.

## 15. Si tu veux écrire un nouveau test, pense comme ça

Pose-toi ces trois questions :

1. **Quel comportement précis je veux garantir ?**
2. **Quelles entrées je donne au code ?**
3. **Quel résultat exact j'attends ?**

Exemple simple :

- « Si je donne `/video`, je veux obtenir `video`. »
- « Si le dossier est vide, je veux obtenir `null`. »
- « Si je donne deux fois le même `seed`, je veux le même fichier. »

Si tu raisonnes comme ça, tu as déjà la base d'un bon test.

## 16. Prochaine étape intéressante

Si tu veux progresser ensuite, la suite logique serait d'ajouter :

1. des **tests d'intégration** pour `index.php` ;
2. des tests sur les **headers HTTP** ;
3. éventuellement une exécution automatique dans GitHub Actions.

Mais pour commencer, la base actuelle est déjà très saine pour comprendre les fondamentaux.

## 17. Résumé très court

Aujourd'hui, le projet teste deux choses :

- le routeur reconnaît bien les bons endpoints ;
- le dépôt de médias gère correctement les dossiers, les fichiers et les `seed`.

Ces tests sont lancés avec PHPUnit via Composer, et ils servent à sécuriser la logique métier la plus simple et la plus importante.

Si tu veux, je peux aussi te préparer un **deuxième document** avec :

- comment écrire ton premier test pas à pas ;
- comment inventer de bons cas de test ;
- la différence entre test unitaire, test d'intégration et test fonctionnel avec des exemples du projet.

