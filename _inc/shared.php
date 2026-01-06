<?php

require_once __DIR__.'/../_vendor/autoload.php';

ini_set('display_errors', true);
error_reporting(-1);

// Ordered for which language to show first in tables
$GLOBALS['-langs'] = [
	'mul' => 'un',
	'dan' => 'dk',
	'kal' => 'gl',
	'eng' => 'gb',
	'deu' => 'de',
	'fra' => 'fr',
	'lat' => 'va',
	'gre' => 'gr',
	];

function get_db($new=false) {
	if ($new || empty($GLOBALS['-db'])) {
		$GLOBALS['-db'] = new \TDC\PDO\MySQL($_SERVER['DB_DATABASE'] ?? $_ENV['DB_DATABASE'], $_SERVER['DB_USERNAME'] ?? $_ENV['DB_USERNAME'], $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD']);
	}
	return $GLOBALS['-db'];
}

function DEBUG($v) {
	echo "\n<!-- ".var_export($v, true)." -->\n";
}

function mtime($f) {
	if (!empty($_REQUEST['pwa']) || !empty($_REQUEST['dev'])) {
		return '';
	}
	if (file_exists($f.'.gz')) {
		return '?'.filemtime($f.'.gz');
	}
	return '?'.filemtime($f);
}

function json_encode_vb($v, $o=0) {
	return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | $o);
}

function search_dicts($st, $os = []) {
	$db = get_db();

	$os['cs'] = $os['cs'] ?? false;
	$os['df'] = $os['df'] ?? false;
	$os['ww'] = $os['ww'] ?? false;
	$os['pm'] = $os['pm'] ?? false;
	$os['xd'] = $os['xd'] ?? false;
	foreach ($GLOBALS['-langs'] as $l => $_) {
		$os['sl_'.$l] = $os['sl_'.$l] ?? false;
		$os['tl_'.$l] = $os['tl_'.$l] ?? false;
	}
	$os['suggest'] = $os['suggest'] ?? false;

	if ($os['ww'] || $os['suggest']) {
		$os['pm'] = true;
	}
	if ($os['suggest']) {
		$os['df'] = false;
	}

	$lower = Transliterator::create('any-lower');
	$ascii = Transliterator::create('any-ascii');

	$ws = [];
	$st = preg_replace('~[^-./\pL\pM\pN\s\pZ#]+~', '', $st);
	$st = trim(preg_replace('~[\s\pZ]{2,}~u', ' ', $st));

	$ss = [$st => true];
	if ($st == '#') {
		$ss = [];
		for ($i=0 ; $i<10 ; ++$i) {
			$ss[$i] = $i;
		}
	}
	else {
		$lc = $st;
		if (!$os['cs']) {
			$lc = $lower->transliterate($st);
			$ss[$lc] = true;
		}
		if (!$os['xd']) {
			$ss[$ascii->transliterate($st)] = true;
			$ss[$ascii->transliterate($lc)] = true;
			if (preg_match('~[æĸ]~iu', $st)) {
				$asc = $st;
				$asc = preg_replace('~æ~iu', 'a', $asc);
				$asc = preg_replace('~ĸ~iu', 'k', $asc);
				$ss[$ascii->transliterate($asc)] = true;
				$asc = $lc;
				$asc = preg_replace('~æ~iu', 'a', $asc);
				$asc = preg_replace('~ĸ~iu', 'k', $asc);
				$ss[$ascii->transliterate($asc)] = true;
			}
		}
	}

	$ss = array_keys($ss);
	$nss = [];
	if (!$os['ww']) {
		foreach ($ss as $k => $s) {
			$nss["$s%"] = true;
			if ($os['df']) {
				$nss["% $s%"] = true;
			}
		}
	}
	else {
		foreach ($ss as $k => $s) {
			$nss["$s"] = true;
			$nss["$s %"] = true;
			if ($os['df']) {
				$nss["% $s %"] = true;
				$nss["% $s"] = true;
			}
		}
	}
	if (!$os['pm']) {
		foreach ($ss as $k => $s) {
			$ss[$k] = "%$s";
		}
	}
	else {
		foreach ($ss as $k => $s) {
			$nss["$s%"] = true;
			if ($os['df']) {
				$nss["% $s%"] = true;
			}
		}
	}

	$ss = array_keys($nss);

	$sql = "SELECT lex_id, lex_lexeme, lex_language FROM kat_lexeme_attrs NATURAL JOIN kat_lexemes WHERE FIND_IN_SET('taaguutit', lex_attrs) AND NOT FIND_IN_SET('hidden', lex_attrs) AND ";
	$args = [];
	foreach ($ss as $sk => $sv) {
		$ss[$sk] = "lex_lexeme LIKE ?";
		$args[] = $sv;
	}
	if ($os['df']) {
		foreach ($ss as $sk => $sv) {
			$ss[$sk] = "lex_definition LIKE ?";
			$args[] = $sv;
		}
	}
	$sql .= '('.implode(' OR ', $ss).')';
	if (!$os['sl_mul']) {
		$sls = [];
		foreach ($GLOBALS['-langs'] as $l => $_) {
			if ($os['sl_'.$l]) {
				$sls[] = "lex_language = ?";
				$args[] = $l;
			}
		}
		if (!empty($sls)) {
			$sql .= ' AND ('.implode(' OR ', $sls).')';
		}
	}
	$sql .= " ORDER BY lex_lexeme ASC";

	//DEBUG($sql);
	//DEBUG($args);

	$ws = [];
	$syns = [];
	$words = [];
	$stm = $db->prepexec($sql, $args);
	while ($row = $stm->fetch()) {
		$ws[$row['lex_id']] = $row;
		$syns[$row['lex_id']] = [];
		$syns[$row['lex_id']][$row['lex_id']] = $row;
		$words[$row['lex_id']] = $row['lex_lexeme'];
	}
	if ($os['suggest']) {
		$words = array_values($words);
		sort($words);
		$words = array_unique($words);
		$words = array_values($words);
		return $words;
	}

	if (empty($ws)) {
		return null;
	}

	$sql = "SELECT lex_id, lex_syn, lex_lexeme, lex_language FROM kat_lexeme_attrs NATURAL JOIN kat_lexemes NATURAL JOIN glue_lexeme_synonyms WHERE FIND_IN_SET('taaguutit', lex_attrs) AND NOT FIND_IN_SET('hidden', lex_attrs) AND lex_syn IN (".implode(', ', array_keys($ws)).")";
	$args = [];
	if (!$os['sl_mul'] && !$os['tl_mul']) {
		$sls = [];
		foreach ($GLOBALS['-langs'] as $l => $_) {
			if ($os['sl_'.$l]) {
				$sls[] = "lex_language = ?";
				$args[] = $l;
			}
			if ($os['tl_'.$l]) {
				$sls[] = "lex_language = ?";
				$args[] = $l;
			}
		}
		if (!empty($sls)) {
			$sql .= ' AND ('.implode(' OR ', $sls).')';
		}
	}
	$stm = $db->prepexec($sql, $args);
	while ($row = $stm->fetch()) {
		$syns[$row['lex_syn']][$row['lex_id']] = $row;
		if (array_key_exists($row['lex_id'], $syns)) {
			unset($syns[$row['lex_id']]);
		}
	}

	return $syns;
}
