<?php

declare(strict_types=1);

use CodeIgniter\Boot;
use Config\Paths;

// Vérifie rapidement la version de PHP avant de lancer le framework.
$minPhpVersion = '8.2';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
	header('HTTP/1.1 503 Service Unavailable', true, 503);
	echo sprintf(
		'Votre version de PHP doit être supérieure ou égale à %s. Version actuelle : %s',
		$minPhpVersion,
		PHP_VERSION,
	);
	exit(1);
}

// Le projet utilise la racine actuelle comme front controller en attendant la migration complète vers `public/`.
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
	chdir(FCPATH);
}

require FCPATH . 'app/Config/Paths.php';

$paths = new Paths();

require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
