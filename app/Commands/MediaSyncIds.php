<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use MediaGoneWild\MediaRepository;

/**
 * Synchronise le manifeste des IDs stables des médias.
 */
final class MediaSyncIds extends BaseCommand
{
    /**
     * Groupe logique de la commande.
     *
     * @var string
     */
    protected $group = 'Media';

    /**
     * Nom de commande appelé via spark.
     *
     * @var string
     */
    protected $name = 'media:sync-ids';

    /**
     * Description affichée dans la liste des commandes.
     *
     * @var string
     */
    protected $description = 'Ajoute les IDs manquants dans media/ids.json sans modifier les IDs existants.';

    /**
     * Usage principal de la commande.
     *
     * @var string
     */
    protected $usage = 'media:sync-ids [--dry-run]';

    /**
     * Décrit les options supportées.
     *
     * @var array<string, string>
     */
    protected $options = [
        '--dry-run' => 'Simule la synchronisation sans écrire le fichier media/ids.json.',
    ];

    /**
     * Exécute la synchronisation des IDs manquants.
     *
     * @param list<string> $params Les paramètres de la commande spark.
     */
    public function run(array $params): void
    {
        $dryRun = in_array('--dry-run', $params, true);

        $repository = new MediaRepository(ROOTPATH . 'media');
        $result = $repository->syncIdsManifest($dryRun);

        if ($result['added'] === 0) {
            CLI::write('Aucun média sans ID détecté. Le manifeste est déjà synchronisé.', 'green');
            CLI::write('Total des IDs connus : ' . $result['total']);

            return;
        }

        if ($dryRun) {
            CLI::write('Simulation terminée : ' . $result['added'] . ' ID(s) manquant(s) seraient ajoutés.', 'yellow');
            CLI::write('Le fichier media/ids.json n\'a pas été modifié.');
            CLI::write('Total après synchronisation simulée : ' . $result['total']);

            return;
        }

        CLI::write('Synchronisation terminée : ' . $result['added'] . ' ID(s) ajouté(s).', 'green');
        CLI::write('Fichier mis à jour : media/ids.json');
        CLI::write('Total des IDs connus : ' . $result['total']);
    }
}

