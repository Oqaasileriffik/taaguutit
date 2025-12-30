<?php
declare(strict_types=1);
require_once __DIR__.'/_inc/shared.php';

header('Content-Type: application/json; charset=UTF-8');

$origin = '*';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
	$origin = trim($_SERVER['HTTP_ORIGIN']);
}
header('Access-Control-Allow-Origin: '.$origin);
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header('HTTP/1.1 200 Options');
	die();
}

$db = get_db(true);

while ($_REQUEST['a'] === 'search' || $_REQUEST['a'] === 'suggest') {
	$q = trim($_REQUEST['q'] ?? '');
	if (empty($q) || !preg_match('~[\pL\pN#]~iu', $q)) {
		$rv['errors'][] = 'No letters or numbers in input: '.$q;
		break;
	}

	$opts = [];
	foreach (['df', 'cs', 'ww', 'pm', 'xd'] as $o) {
		$opts[$o] = intval($_REQUEST['opts'][$o] ?? 0) === 1;
	}
	foreach ($GLOBALS['-langs'] as $l => $_) {
		$opts['sl_'.$l] = intval($_REQUEST['opts']['sl_'.$l] ?? 0) === 1;
		$opts['tl_'.$l] = intval($_REQUEST['opts']['tl_'.$l] ?? 0) === 1;
	}
	$opts['suggest'] = ($_REQUEST['a'] === 'suggest');

	$rv['ws'] = search_dicts($q, $opts);
	if (!is_array($rv['ws'])) {
		unset($rv['ws']);
	}
	break;
}

foreach ($rv as $k => $v) {
	if (empty($v)) {
		unset($rv[$k]);
	}
}

if (!empty($rv['errors'])) {
	header('HTTP/1.1 400 Bad Request');
}
echo json_encode_vb($rv);
