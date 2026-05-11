<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTO\MediaFile;
use App\DTO\MediaTransformOptions;
use App\Services\MediaService;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\ResponseInterface;
use MediaGoneWild\MediaRepository;

/**
 * Expose les endpoints publics de médias aléatoires.
 */
final class MediaController extends BaseController
{
    /**
     * Liste blanche des types de médias autorisés dans l'API.
     *
     * @var list<string>
     */
    private const ALLOWED_MEDIA_TYPES = ['photo', 'video', 'logo'];

    /**
     * Service qui sélectionne les médias et prépare leurs métadonnées HTTP.
     */
    private MediaService $mediaService;

    /**
     * Initialise le service métier utilisé par les endpoints médias.
     */
    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);

        $repository = new MediaRepository(ROOTPATH . 'media');
        $this->mediaService = new MediaService($repository);
    }

    /**
     * Affiche la page d'accueil qui explique l'utilisation des endpoints médias.
     */
    public function index(): ResponseInterface
    {
        return $this->response
            ->setContentType('text/html; charset=UTF-8')
            ->setBody(view('home/index'));
    }

    /**
     * Affiche la documentation complète des paramètres de l'API.
     */
    public function doc(): ResponseInterface
    {
        return $this->response
            ->setContentType('text/html; charset=UTF-8')
            ->setBody(view('doc/index', [
                'allowedFitModes'  => \App\DTO\MediaTransformOptions::ALLOWED_FIT_MODES,
                'allowedExtensions' => \App\DTO\MediaTransformOptions::ALLOWED_EXTENSIONS,
                'defaultQuality'   => \App\DTO\MediaTransformOptions::DEFAULT_QUALITY,
            ]));
    }

    /**
     * Retourne une photo aléatoire.
     */
    public function photo(): ResponseInterface
    {
        return $this->serveMedia('photo');
    }

    /**
     * Retourne une photo aléatoire dans le format spécifié par l'extension de l'URL.
     *
     * Exemple : `/photo.webp?seed=robert&width=800` sert une photo en WebP.
     * L'extension de l'URL est prioritaire sur le paramètre `?extension=` en query string.
     *
     * @param string $extension L'extension de format demandée (jpg, png, webp, gif).
     */
    public function photoWithExtension(string $extension): ResponseInterface
    {
        return $this->serveMedia('photo', strtolower(trim($extension)));
    }

    /**
     * Retourne une vidéo aléatoire.
     */
    public function video(): ResponseInterface
    {
        return $this->serveMedia('video');
    }

    /**
     * Retourne une vidéo aléatoire via une URL avec extension.
     *
     * L'extension est ignorée pour les vidéos (aucune transformation n'est applicable).
     *
     * @param string $extension L'extension présente dans l'URL (non utilisée pour les vidéos).
     */
    public function videoWithExtension(string $extension): ResponseInterface
    {
        return $this->serveMedia('video');
    }

    /**
     * Retourne un logo aléatoire.
     */
    public function logo(): ResponseInterface
    {
        return $this->serveMedia('logo');
    }

    /**
     * Retourne un logo aléatoire via une URL avec extension.
     *
     * Les logos sont des SVG. La transformation raster (PNG, JPG…) n'est pas supportée
     * avec le driver GD : GD ne sait pas décoder les SVG. Avec Imagick + librsvg ce serait
     * possible, mais ces dépendances système ne sont pas disponibles sur ce serveur.
     * L'extension est donc ignorée silencieusement et le SVG est servi tel quel.
     *
     * @param string $extension L'extension présente dans l'URL (non utilisée pour les logos).
     */
    public function logoWithExtension(string $extension): ResponseInterface
    {
        return $this->serveMedia('logo');
    }

    /**
     * Affiche un catalogue simple pour parcourir les médias disponibles.
     */
    public function catalogue(): ResponseInterface
    {
        $syncReport = $this->mediaService->syncIdsForCatalogueIfNeeded();

        if (! $syncReport['synced'] && ($syncReport['missing'] > 0 || $this->mediaService->countOrphanedIds() > 0) && $this->canAutoSyncIds()) {
            $added   = 0;
            $removed = 0;

            if ($this->mediaService->countOrphanedIds() > 0) {
                $cleanupResult = $this->mediaService->cleanupOrphanedIds();
                $removed = $cleanupResult['removed'];
            }

            if ($syncReport['missing'] > 0) {
                $syncResult = $this->mediaService->syncIdsManifest();
                $added = $syncResult['added'];
            }

            $syncReport = [
                'synced'  => $added > 0 || $removed > 0,
                'added'   => $added,
                'removed' => $removed,
                'missing' => $this->mediaService->countMissingIds(),
            ];
        }

        $selectedType = $this->request->getGet('type');
        $selectedType = is_string($selectedType) ? strtolower(trim($selectedType)) : 'all';
        $selectedType = in_array($selectedType, [...self::ALLOWED_MEDIA_TYPES, 'all'], true) ? $selectedType : 'all';

        // Collection : uniquement applicable aux photos, lettres/chiffres/tirets/underscores.
        $rawCollection = $this->request->getGet('collection');
        $selectedCollection = is_string($rawCollection) ? trim($rawCollection) : null;
        $selectedCollection = ($selectedCollection !== null && $selectedCollection !== '' && preg_match('/^[a-z0-9_-]+$/i', $selectedCollection) === 1)
            ? strtolower($selectedCollection)
            : null;

        $limit = $this->request->getGet('limit');
        $limit = is_string($limit) && ctype_digit($limit) ? (int) $limit : 50;
        $limit = max(1, min(500, $limit));

        $page = $this->request->getGet('page');
        $page = is_string($page) && ctype_digit($page) ? (int) $page : 1;
        $page = max(1, $page);

        $shuffleSeed = $this->request->getGet('shuffle');
        $shuffleSeed = is_string($shuffleSeed) && preg_match('/^[a-z0-9]{8,32}$/', $shuffleSeed) === 1
            ? strtolower($shuffleSeed)
            : null;

        $typesToLoad = $selectedType === 'all' ? self::ALLOWED_MEDIA_TYPES : [$selectedType];

        // Récupère les collections disponibles pour les photos afin de les proposer dans le filtre.
        $photoCollections = $this->mediaService->listCollections('photo');

        $catalogueItems = [];

        foreach ($typesToLoad as $mediaType) {
            // Filtre par collection uniquement pour les photos.
            $collectionForType = ($mediaType === 'photo') ? $selectedCollection : null;
            $mediaFiles = $this->mediaService->listMedia($mediaType, 0, $collectionForType);

            foreach ($mediaFiles as $mediaFile) {
                $catalogueItems[] = [
                    'type' => $mediaType,
                    'id' => $mediaFile->getId(),
                    'fileName' => $mediaFile->getFileName(),
                    'mimeType' => $mediaFile->getMimeType(),
                    'fileSize' => $mediaFile->getFileSize(),
                    'collection' => $mediaFile->getCollection(),
                    'url' => site_url($mediaType) . '?' . http_build_query(['id' => $mediaFile->getId()]),
                ];
            }
        }

        if ($shuffleSeed !== null) {
            usort(
                $catalogueItems,
                static function (array $left, array $right) use ($shuffleSeed): int {
                    $leftScore = hash('sha256', $shuffleSeed . '|' . $left['type'] . '|' . $left['id']);
                    $rightScore = hash('sha256', $shuffleSeed . '|' . $right['type'] . '|' . $right['id']);

                    return strcmp($leftScore, $rightScore);
                },
            );
        } else {
            usort(
                $catalogueItems,
                static fn (array $left, array $right): int => strcmp(
                    $left['type'] . '/' . $left['id'],
                    $right['type'] . '/' . $right['id'],
                ),
            );
        }

        $shuffleParams = [
            'type'  => $selectedType,
            'limit' => $limit,
            'page'  => 1,
            'shuffle' => strtolower(bin2hex(random_bytes(6))),
        ];

        if ($selectedCollection !== null) {
            $shuffleParams['collection'] = $selectedCollection;
        }

        $shuffleUrl = site_url('catalogue') . '?' . http_build_query($shuffleParams);

        $totalItems = count($catalogueItems);
        $totalPages = max(1, (int) ceil($totalItems / $limit));
        $currentPage = min($page, $totalPages);
        $offset = ($currentPage - 1) * $limit;
        $catalogueItems = array_slice($catalogueItems, $offset, $limit);

        return $this->response
            ->setContentType('text/html; charset=UTF-8')
            ->setBody(view('catalogue/index', [
                'items'              => $catalogueItems,
                'selectedType'       => $selectedType,
                'selectedCollection' => $selectedCollection,
                'photoCollections'   => $photoCollections,
                'limit'              => $limit,
                'currentPage'        => $currentPage,
                'totalPages'         => $totalPages,
                'totalItems'         => $totalItems,
                'shuffleSeed'        => $shuffleSeed,
                'shuffleUrl'         => $shuffleUrl,
                'idsMissingCount'    => $syncReport['missing'],
                'idsWereSynced'      => $syncReport['synced'],
                'idsAddedCount'      => $syncReport['added'],
                'idsRemovedCount'    => $syncReport['removed'],
            ]));
    }

    /**
     * Retourne un média précis du catalogue à partir de son type et de son nom.
     *
     * @param string $mediaType Le type de média demandé.
     * @param string $fileName Le nom de fichier du média.
     */
    public function catalogueMedia(string $mediaType, string $fileName): ResponseInterface
    {
        $normalizedMediaType = strtolower($mediaType);

        if (! in_array($normalizedMediaType, self::ALLOWED_MEDIA_TYPES, true)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON(['error' => 'Type de média invalide.']);
        }

        $decodedFileName = rawurldecode($fileName);
        $media = $this->mediaService->getMediaByFileName($normalizedMediaType, $decodedFileName);

        return $this->createMediaResponse($media, $normalizedMediaType);
    }

    /**
     * Sert un média binaire pour le type demandé.
     *
     * Pour les photos, les paramètres de transformation image (`width`, `height`, `fit`,
     * `extension`, `quality`, `bgcolor`) sont parsés et transmis au service.
     * L'extension issue de l'URL (ex. `/photo.webp`) est utilisée comme format par défaut
     * si le paramètre `extension` n'est pas fourni en query string.
     * Le paramètre `?collection=` permet de restreindre la sélection aléatoire à une
     * collection (sous-dossier) donnée.
     * Pour les vidéos et les logos, ces paramètres sont ignorés silencieusement.
     *
     * @param string      $mediaType    Le type de média à renvoyer.
     * @param string|null $urlExtension L'extension extraite de l'URL, ou null si absente.
     */
    private function serveMedia(string $mediaType, ?string $urlExtension = null): ResponseInterface
    {
        $id = $this->request->getGet('id');
        $id = is_string($id) ? strtolower(trim($id)) : null;

        // Collection (sous-dossier) : uniquement lettres, chiffres, tirets et underscores.
        $collection = $this->getNullableQueryString('collection');
        $collection = ($collection !== null && preg_match('/^[a-z0-9_-]+$/i', $collection) === 1)
            ? strtolower(trim($collection))
            : null;

        if ($id !== null && $id !== '') {
            $media = $this->mediaService->getMediaById($mediaType, $id);

            if ($media === null && $this->canAutoSyncIds()) {
                $syncResult = $this->mediaService->syncIdsManifest();

                if ($syncResult['added'] > 0) {
                    $media = $this->mediaService->getMediaById($mediaType, $id);
                }
            }

            return $this->createMediaResponse($media, $mediaType, $urlExtension);
        }
        $seed = $this->request->getGet('seed');
        $seed = is_string($seed) ? $seed : null;

        $media = $this->mediaService->pickMedia($mediaType, $seed, $collection);

        return $this->createMediaResponse($media, $mediaType, $urlExtension);
    }

    /**
     * Extrait et valide les options de transformation depuis les paramètres GET de la requête.
     *
     * L'extension issue de l'URL (ex. `/photo.webp`) prend la priorité sur le paramètre
     * `?extension=` en query string. Le paramètre query n'est utilisé qu'en l'absence
     * d'extension dans l'URL.
     *
     * @param string|null $urlExtension Extension extraite de l'URL, ou null si absente.
     *
     * @return MediaTransformOptions Les options normalisées (peut ne contenir aucune option active).
     */
    private function parseTransformOptions(?string $urlExtension = null): MediaTransformOptions
    {
        // L'extension de l'URL est prioritaire ; le paramètre ?extension= sert de repli.
        $effectiveExtension = $urlExtension ?? $this->getNullableQueryString('extension');

        return MediaTransformOptions::fromQueryParams([
            'width'     => $this->getNullableQueryString('width'),
            'height'    => $this->getNullableQueryString('height'),
            'fit'       => $this->getNullableQueryString('fit'),
            'extension' => $effectiveExtension,
            'quality'   => $this->getNullableQueryString('quality'),
            'bgcolor'   => $this->getNullableQueryString('bgcolor'),
        ]);
    }

    /**
     * Retourne un paramètre GET uniquement s'il s'agit bien d'une chaîne scalaire.
     *
     * Cela évite de transmettre des tableaux inattendus au DTO de transformation.
     */
    private function getNullableQueryString(string $key): ?string
    {
        $value = $this->request->getGet($key);

        return is_string($value) ? $value : null;
    }

    /**
     * Prépare la réponse binaire d'un média avec ses en-têtes HTTP.
     *
     * Si le type est `photo`, les options de transformation sont parsées et appliquées
     * en cas de besoin. L'extension de l'URL est utilisée comme format par défaut si
     * le paramètre `extension` n'est pas fourni en query string.
     * Pour les autres types, aucune transformation n'est effectuée.
     *
     * Quand un ID stable est précisé dans la requête (`?id=`), le fichier retourné
     * est toujours le même : on peut envoyer un Cache-Control public pour éviter
     * que le navigateur ne répète des appels PHP inutiles (important sur serveur mutualisé).
     *
     * @param MediaFile|null $media         Le média résolu à renvoyer.
     * @param string         $mediaType     Le type du média (`photo`, `video`, `logo`).
     * @param string|null    $urlExtension  L'extension extraite de l'URL, ou null si absente.
     */
    private function createMediaResponse(
        ?MediaFile $media,
        string $mediaType = '',
        ?string $urlExtension = null,
    ): ResponseInterface {
        if ($media === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON(['error' => 'Aucun média disponible pour ce type.']);
        }

        if (! $media->isReadable()) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['error' => 'Média introuvable ou permissions insuffisantes.']);
        }

        // Applique les transformations uniquement pour les photos raster.
        if ($mediaType === 'photo') {
            $opts = $this->parseTransformOptions($urlExtension);

            if ($opts->hasOptions()) {
                try {
                    $media = $this->mediaService->transformMedia($media, $opts);
                } catch (\Throwable $e) {
                    return $this->response
                        ->setStatusCode(500)
                        ->setJSON(['error' => 'Impossible d\'appliquer la transformation demandée.']);
                }
            }
        }

        $downloadResponse = $this->response->download($media->getPath(), null, false);

        if (! $downloadResponse instanceof DownloadResponse) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['error' => 'Impossible de préparer la réponse du média.']);
        }

        // Un ID stable garantit que l'URL retournera toujours le même fichier :
        // on peut mettre en cache côté navigateur (1 heure) pour éviter des appels PHP
        // redondants, notamment lors du chargement du catalogue sur un serveur mutualisé.
        // Sans ID (sélection aléatoire), on force no-store pour ne pas servir le mauvais média.
        $requestId = $this->request->getGet('id');
        $hasStableId = is_string($requestId) && $requestId !== '';
        $cacheControl = $hasStableId ? 'public, max-age=3600, immutable' : 'no-store';

        return $downloadResponse
            ->setFileName($media->getFileName())
            ->setHeader('Content-Type', $media->getMimeType())
            ->setHeader('Content-Length', (string) $media->getFileSize())
            ->setHeader('Cache-Control', $cacheControl)
            ->inline();
    }

    /**
     * Indique si l'application tourne en environnement développement.
     */
    private function isDevelopmentEnvironment(): bool
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
    }

    /**
     * Indique si la requête courante provient d'un hôte local.
     */
    private function isLocalRequest(): bool
    {
        $host = strtolower($this->request->getUri()->getHost());

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * Autorise la synchronisation automatique des IDs en développement ou en local.
     */
    private function canAutoSyncIds(): bool
    {
        return $this->isDevelopmentEnvironment() || $this->isLocalRequest();
    }
}

