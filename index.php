<?php
require_once __DIR__.'/_inc/shared.php';

$id = intval($_REQUEST['id'] ?? 0);
$search = trim(preg_replace('~[\s\pZ]{2,}~u', ' ', $_REQUEST['st'] ?? ''));
$opts = [
	'df' => ($_REQUEST['df'] ?? false),
	'cs' => ($_REQUEST['cs'] ?? false),
	'ww' => ($_REQUEST['ww'] ?? false),
	'pm' => ($_REQUEST['pm'] ?? false),
	'xd' => ($_REQUEST['xd'] ?? false),
	];

foreach (['mul', 'dan', 'deu', 'eng', 'fra', 'gre', 'lat', 'kal'] as $lang) {
	$opts['sl_'.$lang] = ($_REQUEST['sl_'.$lang] ?? false);
	$opts['tl_'.$lang] = ($_REQUEST['tl_'.$lang] ?? false);
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Taaguutit « Oqaasileriffik</title>

	<link rel="icon" type="image/x-icon" href="favicon.ico">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Gudea%3A100%2C100italic%2C200%2C200italic%2C300%2C300italic%2C400%2C400italic%2C500%2C500italic%2C600%2C600italic%2C700%2C700italic%2C800%2C800italic%2C900%2C900italic%7CRoboto%3A100%2C100italic%2C200%2C200italic%2C300%2C300italic%2C400%2C400italic%2C500%2C500italic%2C600%2C600italic%2C700%2C700italic%2C800%2C800italic%2C900%2C900italic&amp;ver=5.5.3" type="text/css" media="all">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11/font/bootstrap-icons.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7/css/flag-icons.min.css">
	<link rel="alternate" hreflang="da" href="https://taaguutit.gl/da">
	<link rel="alternate" hreflang="kl" href="https://taaguutit.gl/kl">
	<link rel="alternate" hreflang="en" href="https://taaguutit.gl/en">
	<link rel="alternate" hreflang="x-default" href="https://taaguutit.gl/">
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.7/dist/jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3/dist/js/bootstrap.bundle.min.js"></script>

	<script src="_static/l10n.js<?=mtime(__DIR__.'/_static/l10n.js');?>"></script>
	<link rel="stylesheet" href="_static/taaguutit.css<?=mtime(__DIR__.'/_static/taaguutit.css');?>">
	<script src="_static/taaguutit.js<?=mtime(__DIR__.'/_static/taaguutit.js');?>"></script>


<script async src="https://www.googletagmanager.com/gtag/js?id=G-ZQ378LEPCY"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-ZQ378LEPCY');
</script>

<script>
  var _paq = window._paq = window._paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="https://oqaasileriffik.gl/matomo/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '6']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>

</head>
<body class="d-flex flex-column">

<header>
	<div class="container">
	<div class="logo">
		<a href="https://oqaasileriffik.gl/" class="text-decoration-none" target="_blank">
		<h1 data-l10n="HDR_TITLE">Oqaasileriffik</h1>
		<h3 data-l10n="HDR_SUBTITLE">The Language Secretariat of Greenland</h3>
		</a>
	</div>
	</div>

	<div class="menu">
	<div class="container">
		<div class="lang-select">
			<a href="./kl" class="item l10n" data-which="kl" title="Kalaallisut"><span class="fi fi-gl"></span></a>
			<a href="./da" class="item l10n" data-which="da" title="Dansk"><span class="fi fi-dk"></span></a>
			<a href="./en" class="item l10n" data-which="en" title="English"><span class="fi fi-gb"></span></a>
		</div>
	</div>
	</div>
</header>

<div class="container flex-grow-1">

<div class="my-3">
<h1 data-l10n="HDR_TERMS_TITLE">Terms</h1>
<p class="fs-4" data-l10n="HDR_TERMS_SUBTITLE">The Language Council's approved terminology</p>
</div>

<?php
if (!empty($id)) {
	$db = get_db();
	$lexs = [];
	$dom = 0;
	$stm = $db->prepexec("SELECT lex_id, lex_lexeme, lex_language, lex_wordclass, lex_domain, lex_definition, lex_info FROM kat_lexeme_attrs NATURAL JOIN kat_lexemes NATURAL JOIN glue_lexeme_synonyms WHERE (lex_id = ? OR lex_syn = ?) ORDER BY lex_lexeme ASC", [$id, $id]);
	while ($row = $stm->fetch()) {
		$lexs[$row['lex_id']] = $row;
		$dom = $row['lex_domain'];
	}

	$dom = $db->prepexec("SELECT dom_id, dom_code, dom_eng, dom_dan, dom_kal FROM kat_domains WHERE dom_id = ?", [$dom])->fetchAll()[0];

	$refs = [];
	$stm = $db->prepexec("SELECT lex_id, ref_id, ref_reference FROM glue_lexeme_references NATURAL JOIN kat_references WHERE lex_id IN (".implode(', ', array_keys($lexs)).") ORDER BY ref_reference");
	while ($row = $stm->fetch()) {
		$refs[$row['lex_id']][$row['ref_id']] = $row['ref_reference'];
	}

	$wcs = [];
	$stm = $db->prepexec("SELECT wc_class, wc_eng, wc_dan, wc_kal FROM kat_wordclasses");
	while ($row = $stm->fetch()) {
		$wcs[$row['wc_class']] = $row;
	}

	$langs = [];
	foreach ($lexs as $lex) {
		$langs[$lex['lex_language']] = $lex['lex_language'];
	}

	echo '<div class="my-3"><a class="link btnBack" href="#" data-l10n="LBL_BACK">Back to results</a></div>';
	echo '<div class="table-responsive"><table class="table table-striped table-bordered table-hover">';
	foreach ($GLOBALS['-langs'] as $l => $f) {
		if (!array_key_exists($l, $langs)) {
			continue;
		}
		$u = strtoupper($l);
		echo '<tr><th colspan="2"><span class="fi fi-'.$f.'"></span> <span data-l10n="LBL_'.$u.'"></span></th></tr>';
		echo '<tr><td colspan="2">';
		echo '<div class="table-responsive"><table class="table table-striped table-bordered table-hover">';
		foreach ($lexs as $lex) {
			if ($lex['lex_language'] != $l) {
				continue;
			}
			echo '<tr><th data-l10n="LBL_TERM"></th><td class="term">'.htmlspecialchars($lex['lex_lexeme']).'</td></tr>';
			echo '<tr><th data-l10n="LBL_WORDCLASS"></th><td><span class="lang-toggle lang-en">'.htmlspecialchars($wcs[$lex['lex_wordclass']]['wc_eng']).'</span><span class="lang-toggle lang-da">'.htmlspecialchars($wcs[$lex['lex_wordclass']]['wc_dan']).'</span><span class="lang-toggle lang-kl">'.htmlspecialchars($wcs[$lex['lex_wordclass']]['wc_kal']).'</span> (<span class="font-monospace">'.$lex['lex_wordclass'].'</span>)</td></tr>';
			if (!empty($lex['lex_definition'])) {
				echo '<tr><th data-l10n="LBL_DEFINITION"></th><td>'.nl2br(htmlspecialchars($lex['lex_definition'])).'</td></tr>';
			}
			if (!empty($lex['lex_info'])) {
				echo '<tr><th data-l10n="LBL_INFO"></th><td>'.nl2br(htmlspecialchars($lex['lex_info'])).'</td></tr>';
			}
			if (!empty($refs[$lex['lex_id']])) {
				echo '<tr><th data-l10n="LBL_REFERENCE"></th><td>'.nl2br(htmlspecialchars(implode(";\n", $refs[$lex['lex_id']]))).'</td></tr>';
			}
		}
		echo '</table></div>';
		echo '</td></tr>';
	}
	echo '<tr><th><span data-l10n="LBL_DOMAIN"></span></th><td>'.$dom['dom_code'].' <span class="lang-toggle lang-en">'.$dom['dom_eng'].'</span><span class="lang-toggle lang-da">'.$dom['dom_dan'].'</span><span class="lang-toggle lang-kl">'.$dom['dom_kal'].'</span></td></tr>';
	echo '</table></div>';
}
else {
?>

<form class="my-5 text-start" action="./#results" method="get" accept-charset="utf-8" id="p_search">
	<div class="my-3 input-group">
		<input class="form-control" list="suggestions" name="st" type="search" id="st" value="<?=htmlspecialchars($search);?>" autocapitalize="off" data-l10n-placeholder="LBL_PLACEHOLDER" placeholder="Enter search word">
		<button class="btn btn-primary btnSearch" type="submit"><i class="bi bi-search"></i> <span data-l10n="BTN_SEARCH">Search</span></button>
	</div>
	<div class="my-3">
		<label class="form-label"><span data-l10n="LBL_BEGINS_WITH">Term starts with</span> <span class="fw-light">(<a href="#skip_letters" class="fst-italic" data-l10n="LBL_SKIP">skip</a>)</span></label>
		<div class="text-center fs-3">
			<a href="#" class="btnLetter px-1">#</a>
			<a href="#" class="btnLetter px-1">A</a>
			<a href="#" class="btnLetter px-1">B</a>
			<a href="#" class="btnLetter px-1">C</a>
			<a href="#" class="btnLetter px-1">D</a>
			<a href="#" class="btnLetter px-1">E</a>
			<a href="#" class="btnLetter px-1">F</a>
			<a href="#" class="btnLetter px-1">G</a>
			<a href="#" class="btnLetter px-1">H</a>
			<a href="#" class="btnLetter px-1">I</a>
			<a href="#" class="btnLetter px-1">J</a>
			<a href="#" class="btnLetter px-1">K</a>
			<a href="#" class="btnLetter px-1">L</a>
			<a href="#" class="btnLetter px-1">M</a>
			<a href="#" class="btnLetter px-1">N</a>
			<a href="#" class="btnLetter px-1">O</a>
			<a href="#" class="btnLetter px-1">P</a>
			<a href="#" class="btnLetter px-1">Q</a>
			<a href="#" class="btnLetter px-1">R</a>
			<a href="#" class="btnLetter px-1">S</a>
			<a href="#" class="btnLetter px-1">T</a>
			<a href="#" class="btnLetter px-1">U</a>
			<a href="#" class="btnLetter px-1">V</a>
			<a href="#" class="btnLetter px-1">W</a>
			<a href="#" class="btnLetter px-1">X</a>
			<a href="#" class="btnLetter px-1">Y</a>
			<a href="#" class="btnLetter px-1">Z</a>
			<a href="#" class="btnLetter px-1">Æ</a>
			<a href="#" class="btnLetter px-1">Ø</a>
			<a href="#" class="btnLetter px-1">Å</a>
		</div>
	</div>
	<div class="my-3" id="skip_letters">
		<label class="form-label"><span data-l10n="LBL_KEYBOARD">Insert special letter</span> <span class="fw-light">(<a href="#skip_kbd" class="fst-italic" data-l10n="LBL_SKIP">skip</a>)</span></label>
		<div class="mx-3">
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">æ</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ø</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">å</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ĸ</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">â</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">á</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ã</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ê</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">é</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">í</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">î</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ĩ</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ô</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ú</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">û</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">ũ</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">'</button>
			<button type="button" class="btn btn-sm btn-outline-primary btnKey my-1 mx-1">~</button>
		</div>
	</div>
	<div class="container" id="skip_kbd">
		<div class="row align-items-start">
			<div class="col">
				<div class="form-check">
					<label><input class="form-check-input remember" type="checkbox" name="df" id="df" <?=($opts['df'] ? 'checked' : '');?>> <span class="form-check-label" data-l10n="LBL_SEARCH_DEFS">Also search in definitions</span></label>
				</div>
				<div class="form-check">
					<label><input class="form-check-input remember" type="checkbox" name="cs" id="cs" <?=($opts['cs'] ? 'checked' : '');?>> <span class="form-check-label" data-l10n="LBL_SEARCH_CASE">Case sensitive</span></label>
				</div>
				<div class="form-check">
					<label><input class="form-check-input remember" type="checkbox" name="ww" id="ww" <?=($opts['ww'] ? 'checked' : '');?>> <span class="form-check-label" data-l10n="LBL_SEARCH_WORDS">Match whole words</span></label>
				</div>
				<div class="form-check">
					<label><input class="form-check-input remember" type="checkbox" name="pm" id="pm" <?=($opts['pm'] ? 'checked' : '');?>> <span class="form-check-label" data-l10n="LBL_SEARCH_START">Match only from start of word</span></label>
				</div>
				<div class="form-check">
					<label><input class="form-check-input remember" type="checkbox" name="xd" id="xd" <?=($opts['xd'] ? 'checked' : '');?>> <span class="form-check-label" data-l10n="LBL_SEARCH_DIACRITICS">Match diacritics exactly</span></label>
				</div>
			</div>
			<div class="col">
				<span class="fw-bold" data-l10n="LBL_SOURCE_LANG">Source languages</span>
<?php
foreach ($GLOBALS['-langs'] as $l => $f) {
	$u = strtoupper($l);
	$c = $opts['sl_'.$l] ? ' checked' : '';
	echo '<div class="form-check"><label><input class="form-check-input remember" type="checkbox" name="sl_'.$l.'" id="sl_'.$l.'"'.$c.'> <span class="fi fi-'.$f.'"></span> <span class="form-check-label" data-l10n="LBL_'.$u.'">'.$l.'</span></label></div>';
}
?>
			</div>
			<div class="col">
				<span class="fw-bold" data-l10n="LBL_TARGET_LANG">Target languages</span>
<?php
foreach ($GLOBALS['-langs'] as $l => $f) {
	$u = strtoupper($l);
	$c = $opts['tl_'.$l] ? ' checked' : '';
	echo '<div class="form-check"><label><input class="form-check-input remember" type="checkbox" name="tl_'.$l.'" id="tl_'.$l.'"'.$c.'> <span class="fi fi-'.$f.'"></span> <span class="form-check-label" data-l10n="LBL_'.$u.'">'.$l.'</span></label></div>';
}
?>
			</div>
		</div>
	</div>
	<div class="my-3 text-center">
		<button class="mx-3 btn btn-primary btnSearch" type="submit"><i class="bi bi-search"></i> <span data-l10n="BTN_SEARCH">Search</span></button>
		<button class="mx-3 btn btn-outline-secondary btnClear" type="button"><i class="bi bi-arrow-counterclockwise"></i> <span data-l10n="LBL_CLEAR">Clear</span></button>
	</div>
</form>

<datalist id="suggestions">
</datalist>

<div class="my-4" id="results">
<?php
if (!empty($search)) {
	$syns = search_dicts($search, $opts);
	if (empty($syns)) {
		echo '<div class="alert alert-warning my-5" role="alert"><i class="bi bi-exclamation-diamond"></i> <span data-l10n="ERR_NO_HITS"></span></div>';
	}
	else {
		echo '<div class="table-responsive"><table class="table table-striped table-bordered table-hover">';
		$langs = [];
		foreach ($syns as $sid => $ss) {
			foreach ($ss as $lex) {
				$langs[$lex['lex_language']] = $lex['lex_language'];
			}
		}
		asort($langs);
		$order = [];
		foreach ($GLOBALS['-langs'] as $l => $_) {
			if (array_key_exists($l, $langs)) {
				$order[$l] = $l;
			}
		}
		foreach ($GLOBALS['-langs'] as $l => $_) {
			if (array_key_exists($l, $langs) && $opts['tl_'.$l]) {
				unset($order[$l]);
				$order[$l] = $l;
			}
		}
		echo '<thead class="table-dark"><tr>';
		foreach ($order as $l) {
			$u = strtoupper($l);
			echo '<th><span class="fi fi-'.$GLOBALS['-langs'][$l].'"></span> <span data-l10n="LBL_'.$u.'"></span> (<span class="font-monospace">'.$l.'</span>)</th>';
		}
		echo '</tr></thead>';
		echo '<tbody>';
		foreach ($syns as $sid => $ss) {
			echo '<tr>';
			foreach ($order as $l) {
				$out = [];
				foreach ($ss as $lex) {
					if ($lex['lex_language'] == $l) {
						if (array_key_exists('lex_syn', $lex)) {
							$out[] = '<a class="link-dark" href="./?id='.$lex['lex_id'].'">'.$lex['lex_lexeme'].'</a>';
						}
						else {
							$out[] = '<a href="./?id='.$lex['lex_id'].'">'.$lex['lex_lexeme'].'</a>';
						}
					}
				}
				echo '<td>'.implode('; ', $out).'</td>';
			}
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table></div>';
	}
}
?>
</div>

<?php
}
?>

<aside class="alert alert-light" data-l10n="TXT_DISCLAIMER">Please observe that typing errors etc. may exist. The ‘Taaguutaasivik’ termbank is under development. Additions and corrections are made on an ongoing basis.</aside>

</div>

<footer>
	<div class="container footer">
		<section class="row main-footer">
			<div class="col">
				<div class="footer-title">
				<h2 data-l10n="FTR_CONTACT">Contact</h2>
				</div>
				<div class="row flex-nowrap mb-2">
					<div class="col-auto pr-0"><i aria-hidden="true" class="bi bi-envelope-fill"></i></div>
					<div class="col nowrap"><a href="mailto:oqaasileriffik@oqaasileriffik.gl" class="text-decoration-none">oqaasileriffik@oqaasileriffik.gl</a></div>
				</div>
				<div class="row flex-nowrap mb-2">
					<div class="col-auto pr-0"><i aria-hidden="true" class="bi bi-telephone-fill"></i></div>
					<div class="col nowrap"><a href="tel:+299384060" class="text-decoration-none">(+299) 38 40 60</a></div>
				</div>
				<div class="row flex-nowrap">
					<div class="col-auto pr-0"><i aria-hidden="true" class="bi bi-geo-alt-fill"></i></div>
					<div class="col"><a href="https://www.google.com/maps?q=Oqaasileriffik,%20Nuuk" class="text-decoration-none" target="_blank">Ceresvej 7-1<br>Postboks 980<br>3900 Nuuk<br>Kalaallit Nunaat</a></div>
				</div>
			</div>

			<div class="col">
				<div class="footer-title">
				<h2 data-l10n="FTR_HOURS">Opening hours</h2>
				</div>
				<div class="row mb-2">
					<div class="col" data-l10n="FTR_MON_FRI">Monday - Friday</div>
					<div class="col">8:00 - 16:00</div>
				</div>
				<div class="row text-orange">
					<div class="col" data-l10n="FTR_SAT_SUN">Saturday - Sunday</div>
					<div class="col" data-l10n="FTR_CLOSED">Closed</div>
				</div>
			</div>

			<div class="col-auto">
				<div class="footer-title">
				<h2 data-l10n="FTR_NEWS">Newsletter sign-up</h2>
				</div>
				<div class="row mb-4">
					<div class="col" data-l10n="FTR_NEWS_TEXT">Sign up for news via e-mail</div>
				</div>
				<a role="button" class="btn btn-outline-secondary" href="https://groups.google.com/a/oqaasileriffik.gl/forum/#!forum/news/join" target="_blank" rel="noopener">
					<div class="row flex-nowrap">
							<div class="col-auto pr-0"><i aria-hidden="true" class="bi bi-envelope"></i></div>
							<div class="col" data-l10n="FTR_NEWS_BUTTON">Sign up</div>
					</div>
				</a>
			</div>
		</section>
	</div>
	<div class="footer-line">
	</div>
	<div class="footer copyright text-center">
		<section>
			<div><span class="copyr">©</span> 2020-2026 <span class="sep">|</span> Oqaasileriffik</div>
		</section>
	</div>
</footer>

</body>
</html>
