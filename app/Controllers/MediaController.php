<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTO\MediaFile;
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
     * Retourne une vidéo aléatoire.
     */
    public function video(): ResponseInterface
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
     * Affiche un catalogue simple pour parcourir les médias disponibles.
     */
    public function catalogue(): ResponseInterface
    {
        $selectedType = $this->request->getGet('type');
        $selectedType = is_string($selectedType) ? strtolower(trim($selectedType)) : 'all';
        $selectedType = in_array($selectedType, [...self::ALLOWED_MEDIA_TYPES, 'all'], true) ? $selectedType : 'all';

        $limit = $this->request->getGet('limit');
        $limit = is_string($limit) && ctype_digit($limit) ? (int) $limit : 500;
        $limit = max(1, min(500, $limit));

        $page = $this->request->getGet('page');
        $page = is_string($page) && ctype_digit($page) ? (int) $page : 1;
        $page = max(1, $page);

        $typesToLoad = $selectedType === 'all' ? self::ALLOWED_MEDIA_TYPES : [$selectedType];

        $catalogueItems = [];

        foreach ($typesToLoad as $mediaType) {
            $mediaFiles = $this->mediaService->listMedia($mediaType, 0);

            foreach ($mediaFiles as $mediaFile) {
                $catalogueItems[] = [
                    'type' => $mediaType,
                    'fileName' => $mediaFile->getFileName(),
                    'mimeType' => $mediaFile->getMimeType(),
                    'fileSize' => $mediaFile->getFileSize(),
                    'url' => site_url('catalogue/media/' . $mediaType . '/' . rawurlencode($mediaFile->getFileName())),
                ];
            }
        }

        usort(
            $catalogueItems,
            static fn (array $left, array $right): int => strcmp(
                $left['type'] . '/' . $left['fileName'],
                $right['type'] . '/' . $right['fileName'],
            ),
        );

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

        return $this->createMediaResponse($media);
    }

    /**
     * Sert un média binaire pour le type demandé.
     *
     * @param string $mediaType Le type de média à renvoyer.
     */
    private function serveMedia(string $mediaType): ResponseInterface
    {
        $seed = $this->request->getGet('seed');
        $seed = is_string($seed) ? $seed : null;

        $media = $this->mediaService->pickMedia($mediaType, $seed);

        return $this->createMediaResponse($media);
    }

    /**
     * Prépare la réponse binaire d'un média avec ses en-têtes HTTP.
     *
     * @param MediaFile|null $media Le média résolu à renvoyer.
     */
    private function createMediaResponse(?MediaFile $media): ResponseInterface
    {
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
}

