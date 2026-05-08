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

        if (! $syncReport['synced'] && $syncReport['missing'] > 0 && $this->canAutoSyncIds()) {
            $syncResult = $this->mediaService->syncIdsManifest();
            $syncReport = [
                'synced' => $syncResult['added'] > 0,
                'added' => $syncResult['added'],
                'missing' => $this->mediaService->countMissingIds(),
            ];
        }

        $selectedType = $this->request->getGet('type');
        $selectedType = is_string($selectedType) ? strtolower(trim($selectedType)) : 'all';
        $selectedType = in_array($selectedType, [...self::ALLOWED_MEDIA_TYPES, 'all'], true) ? $selectedType : 'all';

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

        $catalogueItems = [];

        foreach ($typesToLoad as $mediaType) {
            $mediaFiles = $this->mediaService->listMedia($mediaType, 0);

            foreach ($mediaFiles as $mediaFile) {
                $catalogueItems[] = [
                    'type' => $mediaType,
                    'id' => $mediaFile->getId(),
                    'fileName' => $mediaFile->getFileName(),
                    'mimeType' => $mediaFile->getMimeType(),
                    'fileSize' => $mediaFile->getFileSize(),
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

        $shuffleUrl = site_url('catalogue') . '?' . http_build_query([
            'type' => $selectedType,
            'limit' => $limit,
            'page' => 1,
            'shuffle' => strtolower(bin2hex(random_bytes(6))),
        ]);

        $totalItems = count($catalogueItems);
        $totalPages = max(1, (int) ceil($totalItems / $limit));
        $currentPage = min($page, $totalPages);
        $offset = ($currentPage - 1) * $limit;
        $catalogueItems = array_slice($catalogueItems, $offset, $limit);

        return $this->response
            ->setContentType('text/html; charset=UTF-8')
            ->setBody(view('catalogue/index', [
                'items' => $catalogueItems,
                'selectedType' => $selectedType,
                'limit' => $limit,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'shuffleSeed' => $shuffleSeed,
                'shuffleUrl' => $shuffleUrl,
                'idsMissingCount' => $syncReport['missing'],
                'idsWereSynced' => $syncReport['synced'],
                'idsAddedCount' => $syncReport['added'],
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
     * Pour les vidéos et les logos, ces paramètres sont ignorés silencieusement.
     *
     * @param string      $mediaType    Le type de média à renvoyer.
     * @param string|null $urlExtension L'extension extraite de l'URL, ou null si absente.
     */
    private function serveMedia(string $mediaType, ?string $urlExtension = null): ResponseInterface
    {
        $id = $this->request->getGet('id');
        $id = is_string($id) ? strtolower(trim($id)) : null;

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

        $media = $this->mediaService->pickMedia($mediaType, $seed);

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

        return $downloadResponse
            ->setFileName($media->getFileName())
            ->setHeader('Content-Type', $media->getMimeType())
            ->setHeader('Content-Length', (string) $media->getFileSize())
            ->setHeader('Cache-Control', 'no-store')
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

