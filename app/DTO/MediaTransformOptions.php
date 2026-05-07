<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Encapsule et valide les options de transformation d'image passées via la requête HTTP.
 *
 * Ces options sont applicables uniquement aux images raster (photos).
 * Les vidéos et les logos SVG ignorent ces paramètres silencieusement.
 */
final readonly class MediaTransformOptions
{
    /**
     * Modes de redimensionnement acceptés par l'API.
     *
     * - contain : redimensionne en conservant le ratio, remplit le reste avec bgcolor (letterbox)
     * - cover   : couvre tout le canvas en recadrant le surplus
     * - fill    : étire sans conserver le ratio (distorsion possible)
     * - scale   : redimensionne proportionnellement (peut ne pas remplir le canvas)
     *
     * @var list<string>
     */
    public const array ALLOWED_FIT_MODES = ['contain', 'cover', 'fill', 'scale'];

    /**
     * Extensions de sortie supportées par l'API.
     *
     * @var list<string>
     */
    public const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Qualité de compression appliquée par défaut si `quality` n'est pas précisé.
     */
    public const int DEFAULT_QUALITY = 85;

    /**
     * Construit les options de transformation avec validation de chaque paramètre.
     *
     * @param int|null    $width     Largeur cible en pixels (≥ 1).
     * @param int|null    $height    Hauteur cible en pixels (≥ 1).
     * @param string|null $fit       Mode de redimensionnement (voir ALLOWED_FIT_MODES).
     * @param string|null $extension Extension de sortie souhaitée (voir ALLOWED_EXTENSIONS).
     * @param int         $quality   Qualité de compression de 1 à 100.
     * @param string|null $bgcolor   Couleur de fond en hex 6 ou 8 chars, ou 'transparent'.
     */
    public function __construct(
        private ?int $width = null,
        private ?int $height = null,
        private ?string $fit = null,
        private ?string $extension = null,
        private int $quality = self::DEFAULT_QUALITY,
        private ?string $bgcolor = null,
    ) {
    }

    /**
     * Retourne la largeur cible demandée, ou null si non précisée.
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * Retourne la hauteur cible demandée, ou null si non précisée.
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Retourne le mode de redimensionnement, ou null si non précisé.
     */
    public function getFit(): ?string
    {
        return $this->fit;
    }

    /**
     * Retourne l'extension de sortie souhaitée, ou null si non précisée.
     */
    public function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * Retourne la qualité de compression (1–100).
     */
    public function getQuality(): int
    {
        return $this->quality;
    }

    /**
     * Retourne la couleur de fond brute telle que fournie dans la requête.
     */
    public function getBgcolor(): ?string
    {
        return $this->bgcolor;
    }

    /**
     * Indique si au moins une option de transformation est présente dans la requête.
     *
     * Retourne false si tous les paramètres sont à leur valeur par défaut ou absents,
     * permettant un court-circuit complet du pipeline de transformation.
     */
    public function hasOptions(): bool
    {
        return $this->width !== null
            || $this->height !== null
            || $this->fit !== null
            || $this->extension !== null
            || $this->quality !== self::DEFAULT_QUALITY
            || $this->bgcolor !== null;
    }

    /**
     * Retourne la valeur alpha (0–255) extraite de la couleur de fond.
     *
     * - 255 = complètement opaque
     * - 0   = complètement transparent
     * - Valeur intermédiaire = semi-transparent
     *
     * La convention est RRGGBBAA pour les hex 8 chars :
     * `ffffff00` = blanc transparent, `ffffff80` = blanc semi-transparent.
     */
    public function getAlpha(): int
    {
        if ($this->bgcolor === null) {
            return 255;
        }

        $lower = strtolower(trim($this->bgcolor, '#'));

        if ($lower === 'transparent') {
            return 0;
        }

        // 8 chars = RRGGBBAA, les 2 derniers = alpha
        if (strlen($lower) === 8) {
            return (int) hexdec(substr($lower, 6, 2));
        }

        // 6 chars = RRGGBBhex sans alpha → opaque
        return 255;
    }

    /**
     * Indique si la couleur de fond implique une transparence partielle ou totale.
     */
    public function hasTransparency(): bool
    {
        return $this->getAlpha() < 255;
    }

    /**
     * Retourne la couleur de fond dans un format accepté par Intervention Image v4.
     *
     * Retourne null si aucune couleur de fond n'a été fournie.
     * Les hex 6 ou 8 chars sont renvoyés tels quels (sans dièse).
     * Le mot-clé `transparent` est traduit en `00000000`.
     */
    public function getBgcolorForIntervention(): ?string
    {
        if ($this->bgcolor === null) {
            return null;
        }

        $cleaned = ltrim($this->bgcolor, '#');
        $lower   = strtolower($cleaned);

        if ($lower === 'transparent') {
            return '00000000';
        }

        return $cleaned;
    }

    /**
     * Génère une clé de cache SHA-256 unique pour cette combinaison d'ID et d'options.
     *
     * @param string $mediaId L'identifiant stable du média source.
     *
     * @return string La clé SHA-256 hexadécimale à 64 caractères.
     */
    public function toCacheKey(string $mediaId): string
    {
        return hash('sha256', implode('|', [
            $mediaId,
            (string) $this->width,
            (string) $this->height,
            (string) $this->fit,
            (string) $this->getNormalizedExtension(),
            (string) $this->quality,
            (string) $this->bgcolor,
        ]));
    }

    /**
     * Retourne l'extension de sortie sous une forme canonique stable.
     *
     * `jpeg` est normalisé en `jpg` pour éviter les doublons de cache.
     */
    public function getNormalizedExtension(): ?string
    {
        if ($this->extension === null) {
            return null;
        }

        return $this->extension === 'jpeg' ? 'jpg' : $this->extension;
    }

    /**
     * Construit un objet d'options à partir des paramètres bruts de la requête HTTP.
     *
     * Chaque valeur est validée et normalisée ; les valeurs invalides sont ignorées
     * (aucun 400 n'est renvoyé pour les paramètres non conformes).
     *
     * @param array<string, string|null> $params Les paramètres GET bruts.
     */
    public static function fromQueryParams(array $params): self
    {
        // --- width ---
        $width = null;
        if (isset($params['width']) && preg_match('/^\d+$/', (string) $params['width']) === 1) {
            $w = (int) $params['width'];
            if ($w >= 1) {
                $width = $w;
            }
        }

        // --- height ---
        $height = null;
        if (isset($params['height']) && preg_match('/^\d+$/', (string) $params['height']) === 1) {
            $h = (int) $params['height'];
            if ($h >= 1) {
                $height = $h;
            }
        }

        // --- fit ---
        $fit = null;
        if (isset($params['fit']) && is_string($params['fit'])) {
            $normalized = strtolower(trim($params['fit']));
            if (in_array($normalized, self::ALLOWED_FIT_MODES, true)) {
                $fit = $normalized;
            }
        }

        // --- extension ---
        $extension = null;
        if (isset($params['extension']) && is_string($params['extension'])) {
            $normalized = strtolower(trim($params['extension']));
            if (in_array($normalized, self::ALLOWED_EXTENSIONS, true)) {
                $extension = $normalized === 'jpeg' ? 'jpg' : $normalized;
            }
        }

        // --- quality ---
        $quality = self::DEFAULT_QUALITY;
        if (isset($params['quality']) && preg_match('/^\d+$/', (string) $params['quality']) === 1) {
            $q = (int) $params['quality'];
            if ($q >= 1 && $q <= 100) {
                $quality = $q;
            }
        }

        // --- bgcolor ---
        $bgcolor = null;
        if (isset($params['bgcolor']) && is_string($params['bgcolor'])) {
            $raw     = trim($params['bgcolor']);
            $cleaned = ltrim($raw, '#');
            $lower   = strtolower($cleaned);

            if ($lower === 'transparent') {
                $bgcolor = 'transparent';
            } elseif (preg_match('/^[0-9a-f]{6}([0-9a-f]{2})?$/i', $cleaned) === 1) {
                $bgcolor = strtolower($cleaned);
            }
        }

        return new self(
            width:     $width,
            height:    $height,
            fit:       $fit,
            extension: $extension,
            quality:   $quality,
            bgcolor:   $bgcolor,
        );
    }
}




