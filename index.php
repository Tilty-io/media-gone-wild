<?php

declare(strict_types=1);

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (is_file($autoloadPath)) {
	require_once $autoloadPath;
}

$routerClass = 'MediaGoneWild\\Router';
$repositoryClass = 'MediaGoneWild\\MediaRepository';

if (!class_exists($routerClass) || !class_exists($repositoryClass)) {
	require_once __DIR__ . '/src/Router.php';
	require_once __DIR__ . '/src/MediaRepository.php';
}

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';

$router = new MediaGoneWild\Router();
$mediaType = $router->resolveMediaType($uriPath);

if ($mediaType === null) {
	http_response_code(404);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['error' => 'Endpoint introuvable. Utilisez /photo, /video ou /logo.']);
	exit;
}

$seed = isset($_GET['seed']) && is_string($_GET['seed']) ? trim($_GET['seed']) : null;
$seed = $seed !== '' ? $seed : null;

$repository = new MediaGoneWild\MediaRepository(__DIR__ . '/media');
$mediaPath = $repository->pick($mediaType, $seed);

if ($mediaPath === null) {
	http_response_code(404);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['error' => 'Aucun média disponible pour ce type.']);
	exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($mediaPath) ?: 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Cache-Control: no-store');
readfile($mediaPath);

