# Synchronisation vers serveur mutualisé

Ce document explique quoi envoyer sur le serveur et quoi exclure pour déployer le projet sans embarquer des fichiers inutiles.

## Checklist rapide

- préparer le build en local ;
- synchroniser les fichiers applicatifs ;
- exclure les dossiers de développement ;
- conserver les dossiers runtime `writable/` ;
- vérifier les endpoints après upload.

## 1) Fichiers à uploader

Uploader ces chemins :

- `app/`
- `media/`
- `public/`
- `src/`
- `vendor/`
- `writable/` (avec ses fichiers `index.html` et `.htaccess`)
- `index.php`
- `.htaccess`
- `composer.json`
- `composer.lock`
- `spark` (utile si vous exécutez des commandes CI4 sur le serveur)

## 2) Fichiers à ne pas uploader

Ne pas uploader ces chemins :

- `.git/`
- `.github/`
- `.idea/`
- `tests/`
- `about-test.md`
- `phpunit.xml.dist`
- `.phpunit.result.cache`
- `composer.phar`
- `README.md` (optionnel en production)
- `LICENSE` (optionnel en production)

## 3) Cas particulier de `writable/`

Le dossier `writable/` est utilisé au runtime (cache, logs, session, uploads).

- il doit exister sur le serveur ;
- il doit être inscriptible par PHP ;
- il ne doit pas être exposé publiquement ;
- conserver les fichiers sentinelles comme `writable/cache/index.html` et `writable/cache/.htaccess`.

## 4) Préparation locale avant synchro

Depuis la racine du projet :

```powershell
php composer.phar install --no-dev --optimize-autoloader
```

Cette commande prépare un `vendor/` orienté production (sans dépendances de test).

## 5) Exemple de stratégie de synchro

### Option A : upload complet via FTP/SFTP

- envoyer tous les chemins listés dans "Fichiers à uploader" ;
- exclure explicitement ceux listés dans "Fichiers à ne pas uploader".

### Option B : artefact local puis upload

- générer un dossier `release/` local ;
- copier uniquement les chemins nécessaires ;
- uploader ce dossier de release.

## 6) Vérification après déploiement

Tester au minimum :

- `/photo`
- `/video`
- `/logo`
- une URL inconnue (doit renvoyer un JSON 404)

Exemple rapide :

```powershell
curl.exe -s -D - -o NUL "https://votre-domaine/photo"
curl.exe -s -D - -o NUL "https://votre-domaine/logo?seed=robert"
curl.exe -s "https://votre-domaine/introuvable"
```

## 7) Note sur la racine web en mutualisé

Le projet actuel fonctionne avec un `index.php` en racine et une réécriture Apache dans `.htaccess`.

Si votre hébergeur permet de pointer la racine web vers `public/`, c'est encore plus propre. Sinon, la configuration actuelle reste compatible avec un mutualisé classique.

