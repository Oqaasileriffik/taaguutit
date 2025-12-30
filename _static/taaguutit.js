'use strict';

/*
Force l10n.php to keep these strings:
'LBL_MUL'
'LBL_DAN'
'LBL_ENG'
'LBL_KAL'
'LBL_LAT'
'LBL_FRA'
'LBL_DEU'
'LBL_GRE'
*/

let _g = {
	suggest: null,
	lang: 'en',
	// Ordered for which language to show first in tables
	langs: ['mul', 'dan', 'kal', 'eng', 'deu', 'fra', 'lat', 'gre'],
};

function escHTML(t) {
	let nt = t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
	return nt;
}

function ls_get(key, def) {
	let v = null;
	try {
		v = window.localStorage.getItem(key);
	}
	catch (e) {
	}
	if (v === null) {
		v = def;
	}
	else {
		v = JSON.parse(v);
	}
	return v;
}

function ls_set(key, val) {
	try {
		window.localStorage.setItem(key, JSON.stringify(val));
	}
	catch (e) {
	}
}

function l10n_detectLanguage() {
	_g.lang = ls_get('lang', navigator.language).replace(/^([^-_]+).*$/, '$1');
	if (/\/(da|en|kl)$/i.test(location.pathname)) {
		_g.lang = location.pathname.slice(-2);
	}
	if (!l10n.s.hasOwnProperty(_g.lang)) {
		_g.lang = 'en';
	}
	return _g.lang;
}

function l10n_translate(s, g) {
	s = '' + s; // Coerce to string

	if (s === 'EMPTY') {
		return '';
	}

	let t = '';

	// If the string doesn't exist in the locale, fall back
	if (!l10n.s[_g.lang].hasOwnProperty(s)) {
		// Try English
		if (l10n.s.hasOwnProperty('en') && l10n.s.en.hasOwnProperty(s)) {
			t = l10n.s.en[s];
		}
		// ...then Danish
		else if (l10n.s.hasOwnProperty('da') && l10n.s.da.hasOwnProperty(s)) {
			t = l10n.s.da[s];
		}
		// ...give up and return as-is
		else {
			t = s;
		}
	}
	else {
		t = l10n.s[_g.lang][s];
	}

	let did = false;
	do {
		did = false;
		let rx = /\{([A-Z0-9_]+)\}/g;
		let ms = [];
		let m = null;
		while ((m = rx.exec(t)) !== null) {
			ms.push(m[1]);
		}
		for (let i=0 ; i<ms.length ; ++i) {
			let nt = l10n_translate(ms[i]);
			if (nt !== ms[i]) {
				t = t.replace('{'+ms[i]+'}', nt);
				did = true;
			}
		}

		rx = /%([a-zA-Z0-9]+)%/;
		m = null;
		while ((m = rx.exec(t)) !== null) {
			let rpl = '\ue001'+m[1]+'\ue001';
			if (typeof g === 'object' && g.hasOwnProperty(m[1])) {
				rpl = g[m[1]];
			}
			t = t.replace(m[0], rpl);
			did = true;
		}
	} while (did);

	t = t.replace(/\ue001/g, '%');
	t = t.replace(/<a>(.+?)<\/a>/g, '<a href="$1">$1</a>');
	return t;
};

function _l10n_world_helper() {
	let e = $(this);
	let k = e.attr('data-l10n');
	let v = l10n_translate(k);

	if (k === v) {
		return;
	}

	if (/^TXT_/.test(k)) {
		v = '<p>'+v.replace(/\n+<ul>/g, '</p><ul>').replace(/\n+<\/ul>/g, '</ul>').replace(/<\/ul>\n+/g, '</ul><p>').replace(/\n+<li>/g, '<li>').replace(/\n\n+/g, '</p><p>').replace(/\n/g, '<br>')+'</p>';
	}
	e.html(v);
	if (/^TXT_/.test(k)) {
		l10n_world(e);
	}
}

function l10n_world(node) {
	if (!node) {
		node = document;
	}
	$(node).find('[data-l10n]').each(_l10n_world_helper);
	$(node).find('[data-l10n-alt]').each(function() {
		let e = $(this);
		let k = e.attr('data-l10n-alt');
		let v = l10n_translate(k);
		e.attr('alt', v);
	});
	$(node).find('[data-l10n-href]').each(function() {
		let e = $(this);
		let k = e.attr('data-l10n-href');
		let v = l10n_translate(k);
		e.attr('href', v);
	});
	$(node).find('[data-l10n-placeholder]').each(function() {
		let e = $(this);
		let k = e.attr('data-l10n-placeholder');
		let v = l10n_translate(k);
		e.attr('placeholder', v);
	});
	if (node === document) {
		$('html').attr('lang', _g.lang);
	}
}

function keyHit(e) {
	e.preventDefault();
	let sb = $(this).closest('form').find('input').first();
	sb.val($.trim(sb.val())+$(this).text());
	sb.trigger('keyup');
	sb.focus();
	return false;
}

function firstLetter(e) {
	e.preventDefault();
	let v = $(this).text();
	$('#st').val(v);
	$('#pm').prop('checked', true);
	$('#df').prop('checked', false);
	$('.btnSearch').first().click();
	return false;
}

function doSuggest() {
	if (_g.suggest) {
		clearTimeout(_g.suggest);
	}
	_g.suggest = null;

	let txt = $.trim($('#st').val());
	if (!txt) {
		return;
	}

	let txts = txt.split(' ');
	txt = txts[txts.length-1];
	txts = txts.slice(0, -1);

	let opts = {
		df: $('#df').prop('checked') ? 1 : 0,
		cs: $('#cs').prop('checked') ? 1 : 0,
		ww: $('#ww').prop('checked') ? 1 : 0,
		pm: $('#pm').prop('checked') ? 1 : 0,
		xd: $('#xd').prop('checked') ? 1 : 0,
		};
	_g.langs.forEach(function(lang) {
		opts['sl_'+lang] = $('#sl_'+lang).prop('checked') ? 1 : 0;
		opts['tl_'+lang] = $('#tl_'+lang).prop('checked') ? 1 : 0;
	});

	Object.keys(opts).forEach(function(k) {
		if (!opts[k]) {
			delete opts[k];
		}
	});

	$.ajax({
		url: './callback.php',
		type: 'POST',
		dataType: 'json',
		data: {a: 'suggest', q: txt, opts: opts},
	}).done(function(rv) {
		let txt = txts.join(' ');
		let opts = '';
		for (let i=0 ; i<rv.ws.length ; ++i) {
			opts += '<option value="'+escHTML($.trim(txt + ' ' + rv.ws[i]))+'"></option>';
		}
		$('#suggestions').html(opts);
	});
}

function queueSuggest() {
	if (_g.suggest) {
		clearTimeout(_g.suggest);
	}
	_g.suggest = setTimeout(doSuggest, 250);
}

function changeLangs() {
	let v = $(this).attr('id');
	let pfx = v.slice(0, 3);
	let which = v.slice(3);
	if (which === 'mul') {
		_g.langs.forEach(function(lang) {
			$('#'+pfx+lang).prop('checked', false).trigger('remember');
		});
		$('#'+pfx+'mul').prop('checked', true).trigger('remember');
	}
	else {
		$('#'+pfx+'mul').prop('checked', false).trigger('remember');
	}
}

$(window).on('load', function() {
	$('.btnKey').click(keyHit);
	$('.btnLetter').click(firstLetter);

	$('a.l10n').click(function(e) {
		e.preventDefault();
		_g.lang = $(this).attr('data-which');
		ls_set('lang', _g.lang);
		l10n_world();
		return false;
	});

	$('.remember').on('remember', function() {
		let e = $(this);
		let id = e.attr('id');
		if (/^d_/.test(id)) {
			id = id.substr(2);
		}
		ls_set('remember-'+id, e.prop('checked'));
	}).on('change', function() {
		$(this).trigger('remember');
	}).each(function() {
		let e = $(this);
		let id = e.attr('id');
		if (/^d_/.test(id)) {
			id = id.substr(2);
		}
		let def = false;
		if (id == 'pm' || id == 'sl_mul' || id == 'tl_kal') {
			def = true;
		}
		e.prop('checked', ls_get('remember-'+id, def));
	});

	_g.langs.forEach(function(lang) {
		$('#sl_'+lang).change(changeLangs);
		$('#tl_'+lang).change(changeLangs);
	});

	$('#st').on('keyup', queueSuggest);

	l10n_detectLanguage();
	l10n_world();
});
