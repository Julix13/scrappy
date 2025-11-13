<?php
	/* Programme permettant d'afficher le code source de fichiers de son dossier
	 *
	 * Appel : debug.php/nomDuFichierOuDossier
	 *
	 * Version : 10 (12 septembre 2025)
	 ********************/

	// version de cette application
	$appVersion = '10 (2025-09-09)';

    // nom de cette application
	$appName = basename(__FILE__);

	// nom du chemin à traiter, par défaut : «» (dossier courant)
	$f = isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO']: '';

    // URI de cette application (y compris le chemin à afficher)
    $currentURI = $_SERVER['PHP_SELF'];

    // URI de cette application, sans le chemin à afficher
	$appURI = (strlen($f)? substr($currentURI, 0, -strlen($f)): $currentURI). '/';

	// suppression d'un «/» éventuel au début ou à la fin
	$f = preg_replace(':(^/|/$):', '', $f);

    // chemin vide → dossier courant
	if ( $f == '' ) { $f = '.'; }

	// paramètre d'action
	$appAction = isset($_REQUEST['action'])? $_REQUEST['action']: NULL;

	if ( is_null($appAction) && is_dir($f) ) {
		// URI canonique (sans paramètres)
		$currentURI = ($f == '.')? $appURI: ($appURI. $f. '/');
		// URI non canonique → on recharge
		if ( $currentURI != $_SERVER['PHP_SELF'] ) {
			header('Location: '. $currentURI);
			exit;
		}
        $appAction = 'listing';
	}

	// Commentaires magiques
    // Ceci permet de donner un affichage particulier pour certains éléments en affichage source.
    // S'utilise en encadrant la partie à traiter par  et , où MMM est le nom
    // de la fonction de traitement:
	// • nop : ne fait rien (permet de ne pas interpréter de commentaire magique)
	// • var : entoure d'une balise <var> (italiques)
	// • span : entoure d'une balise <span> (neutre)
	// • invisible : entoure d'une balise qui rend ce contenu invisible
	// • secret : remplace par l'indication «…secret…»
	// Il est possible d'ajouter des guillemets à cette fonction: 'secret(…)' ou "secret(…)".
	$hooks =
		($appAction == 'download') ?
		[ /* commentaires magiques en mode « téléchargement » */
			'nop' => Array('pre'=> '/*nop(*'.'(/', 'post'=>'/*)*'.'/'),
			'span' => Array('pre'=>'', 'post'=>''),
			'var' => Array('pre'=>'', 'post'=>''),
			'secret' => Array('sub'=>'…secret…'),
			'invisible' => Array('sub'=>''),
		] : [ /* commentaires magiques en mode « affichage » */
			'nop' => Array('pre'=>'', 'post'=>''),
			'span' => Array('pre'=>'<span>', 'post'=>'</span>'),
			'var' => Array('pre'=>'<var>', 'post'=>'</var>'),
			'secret' => Array('sub'=>'<span class="secret">…secret…</span>'),
			'invisible' => Array('pre'=>'<span class="invisible">', 'post'=>'</span>'),
		];

	// extensions connues (pour être affichables)
    // liste des icones : https://fontawesome.com/v4.7/icons/
    $extensions = [
		'/'		=> ['actions'=> 'x',  'icon'=>'folder-o',   'name'=> 'dossier'],
		'pdf'	=> ['actions'=> '',   'icon'=>'file-pdf-o', 'name'=> 'document PDF'],
		'png'	=> ['actions'=> 'i',  'icon'=>'picture-o',  'name'=> 'image PNG'],
		'jpg'	=> ['actions'=> 'i',  'icon'=>'picture-o',  'name'=> 'image JPEG'],
		'jpeg'	=> ['actions'=> 'i',  'icon'=>'picture-o',  'name'=> 'image JPEG'],
		'webp'	=> ['actions'=> 'i',  'icon'=>'picture-o',  'name'=> 'image WebP'],
		'gif'	=> ['actions'=> 'i',  'icon'=>'picture-o',  'name'=> 'image GIF'],
		'css'	=> ['actions'=> 'sC', 'icon'=>'css3',       'name'=> 'feuille de style CSS'],
		'html'	=> ['actions'=> 'sH', 'icon'=>'html5',      'name'=> 'document (X)HTML'],
		'htm'	=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'document HTML (évitez l’extension «htm»)'],
		'mathml'=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'document MathML'],
		'svg'	=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'image vectorielle SVG'],
		'php'	=> ['actions'=> 'Sd', 'icon'=>'file-code-o','name'=> 'script PHP'],
		'twig'	=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'modèle TWIG'],
		'es'	=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'script ECMAscript'],
		'js'	=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'script Javascript / ECMAscript'],
		'json'	=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'données JSON(P)'],
		'jsonp'	=> ['actions'=> 's',  'icon'=>'file-code-o','name'=> 'données JSONP'],
		'sql'	=> ['actions'=> 's',  'icon'=>'database',   'name'=> 'script/données SQL'],
		'txt'	=> ['actions'=> '',   'icon'=>'file-text-o','name'=> 'texte brut'],
		NULL	=> ['actions'=> '',   'icon'=>'file-o',     'name'=> 'inconnu']
    ];

	/* Exécution ou préparation de l’action demandée */
	if ($appAction == 'download') {
		header('Content-Type: application/octet-stream');
	} elseif ($appAction == 'info') {
		header('Content-Type: application/json; charset=utf8');
		echo JSON_encode([
			'version' => $appVersion,
			'name' => $appName,
			'extensions' => $extensions
		]);
		exit;
	} else { header('Content-Type: text/html; charset=utf8'); }

	/* affichage des tailles de fichiers (cf. documentation PHP) */
	function human_filesize($octets, $decimales = 2) {
		$prefixeUnite = 'kMGTP';
		$facteur = floor((strlen($octets) - 1) / 3);
		if ( $facteur ) return(sprintf("%.{$decimales}f", $octets / pow(1024, $facteur)). @$prefixeUnite[$facteur-1]. 'o');
		return($octets.'o');
	}

	/* raccourci d'écriture : icone */
	function icon($n, $c = '') {
		return('<i class="fa fa-'. $n. ' '. $c. '" aria-hidden="true"></i>');
	}

    /* fonction pour taguer une boucle dans un code source */
    $motif_boucle = '/(&lt;\?php\s+(foreach|for|while)\(.+\)\s*{)(\s*\/\*\s*@@\s*d(&eacute;|é|e)but de boucle\s*(\:\s*([^@]+))?\s*@@\s*\*\/)(\s*\?&gt;)([^@]*)(&lt;\?php\s+})(\s*\/\*\s*@@\s*fin de boucle\s*@@\s*\*\/)(\s*\?&gt;)/U';
    function tague_boucle($matches) {
        static $numBoucle = 0;
        $numBoucle ++;
        return("<span class=\"boucle--debut\" data-boucle=\"$numBoucle\">".
            $matches[1]. ($matches[5]? (' /* '.$matches[6].' */'): ''). $matches[7].'</span>'.
            $matches[8].
            "<span class=\"boucle--fin\" data-boucle=\"$numBoucle\">".
            $matches[9].$matches[11].'</span>'
        );
    }
?>
<?php ob_start(); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf8" />
		<?php /* <title>Code source de fichiers PHP, HTML, CSS, JS…</title> */ ?>
		<title><?= ($appAction == 'listing')?'Dossier':'Source', ' : ', $f, ' [', $appName, ']' ?></title>
        <link rel="shortcut icon" type="image/png" href="https://azrael.sha.univ-poitiers.fr/azrael/favicon.png">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.21.0/themes/prism.min.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.21.0/plugins/line-numbers/prism-line-numbers.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.21.0/plugins/inline-color/prism-inline-color.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.21.0/plugins/wpd/prism-wpd.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.21.0/plugins/previewers/prism-previewers.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.21.0/plugins/autolinker/prism-autolinker.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/line-highlight/prism-line-highlight.min.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.11.1/baguetteBox.min.css" integrity="sha512-NVt7pmp5f+3eWRPO1h4A1gCf4opn4r5z2wS1mi7AaVcTzE9wDJ6RzMqSygjDzYHLp+mAJ2/qzXXDHar6IQwddQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<style>
            /* variables */
            :root {
                --interligne: 1.2;
                --reduction-bouton: 0.8;
				--couleur-texte: #000000;
				--couleur-liens: #0000EE;
            }
            /* mise en forme générale */
            body {
                font-family: Arial, Helvetica, sans-serif;
                line-height: var(--interligne);
				color: var(--couleur-texte);
            }
            a:link, a:visited { text-decoration: none; color: var(--couleur-liens); }
            a:hover { text-decoration: underline; }

			h1 { margin: 1ex 0; }
			p { padding: 1ex 0 1ex 1ex; margin: 0; }

			h2 { font-size: 118.92%; }
			h1 { font-size: 141.42%; }

            /* affichage des dossiers */
			table {
				border-collapse: separate;
				border-spacing: 0;
			}
			table td, table th {
				border-bottom: 1px solid #CCCCCC;
				margin: 0; padding: 1px 8px;
			}
			td.size { text-align: right; }
            td.icon { text-align: center; }
            tbody tr:nth-child(2n)   { background-color: #FFFFFF; }
            tbody tr:nth-child(2n+1) { background-color: #F7F7F7; }
            tbody tr:hover, thead tr, tfoot tr, tbody th { background-color: #E0E0EE; }
            thead th, tfoot th { font-weight: bold; }
            tbody th { font-weight: normal; }

            a.action, button.action {
                display: inline-block;
                font-size: calc(var(--reduction-bouton)*1em); line-height: var(--interligne);
                color: #000000; background-color: #F0F0F0;
                margin: 2px; border-radius: 3px; padding: 1px 0.5ex;
                border: 1px solid #CCCCCC;
            }
            .action:hover {
                background-color: #CCCCCC; text-decoration: none;
                border-color: #999999;
            }
            .action:active { background-color: #999999; }
            table { line-height: calc(var(--reduction-bouton)*var(--interligne)*1em + 8px); }
            .action .fa-times { color: #900; }
            .action .fa-plus { color: #060; }
            .action .fa-code { color: #036; }
            .action .fa-check, .action .fa-code.verif { color: #630; }
            .action .fa-download { color: #036; }
            .action .fa-folder-open-o { color: var(--couleur-liens); }

			.updir::after {
				content: '··';
				display: inline-block;
				width: 0; margin: 0 13px 0 -13px;
				font-weight: bold;
			}

            /* affichage des sources */
			.attention { color:#F00; }
			.secret { background-color: rgba(153,153,153,0.7); border: 1px solid #666; color: #666; text-shadow: none; }
			.invisible { display: none; }
			aside { margin: 1em; padding: 1ex; border: 1px solid #C60; background-color: #FC8; }
            aside.erreur { border-color: #C00; background-color: #FCC; }
            aside.OK { border-color: #3C6; background-color: #9FC; }

			/* mise en valeur des boucles et des valeurs PHP */
			.token {
				z-index: 20;
				position: relative;
			}
			.boucle {
				display:inline-block; width: 0; height: 0;
				position: absolute; top: -5px; left: -9px; z-index: 10;
			}
			.boucle path {
				stroke-linecap: round;
				stroke-width: 2px;
				stroke:	hsl(34,100%,40%);
				fill:	hsl(34,100%,95%);
			}
			.boucle text {
				fill: hsl(34,100%,40%);
				font-size: 10px;
			}
			.valeur {
				position: absolute; top: -1px; left: -2px; z-index: 10;
			}
			.valeur path {
				stroke-linecap: round;
				stroke-width: 2px;
				stroke:	hsl(214,100%,40%);
				fill:	hsl(214,100%,95%);
			}
		</style>
	</head>
	<body class="<?= $appAction ?>">
		<?php
			if ( preg_match(':(^|/)\..+:', $f) ) {
				// on interdit le « . » initial dans les demandes
				// pour éviter de remonter dans l'arborescence ou de lire les fichiers cachés

				?><header><h1>Requète&nbsp;: <code><?= $f ?></code></h1></header>
				<main><p><b class="attention">Erreur&nbsp;: le point n'est pas admis dans les URI de ce programme.</b></p></main>
				<?php

			} elseif ( ! is_readable($f) ) {
				// cas d'un fichier ou dossier pas lisible

				?><header><h1>Chemin&nbsp;: <code><?= $f ?></code></h1></header>
				<main><p><b class="attention">Ce document n'existe pas ou n'est pas lisible.</b></p></main>
				<?php

			} elseif ( is_dir($f) ) {
				// cas d'un dossier

				?><header><h1>Dossier&nbsp;: <?= $f ?></h1></header>
				<main>
                    <section>
                        <h2><span class="fa fa-folder-open-o"></span> Contenu du dossier</h2>
                        <table>
                            <thead>
                                <tr><th>&nbsp;</th><th>Nom</th><th>Opérations</th><th>Dernière modification</th><th>Taille</th><th>Type</th></tr>
                            </thead>
                            <tbody><?php
                                $path = ($f == '.')? '../': (str_repeat('../', substr_count($f, '/') + 2). $f. '/');
								?><tr data-entry=".." data-ext="/">
									<td class="icon">
										<?= icon($extensions['/']['icon'], 'updir') ?>
									</td>
									<td class="name">
										<a href="<?= ($f == '.')?'':$path, '../' ?>"><em><?= ($f == '.')?"sortir de $appName": 'dossier parent'?></em></a>
									</td>
									<td class="actions">
										<?php if ($f != '.') { ?>
											<a class="action" href="<?= $currentURI ?>../"><?= icon('folder-open-o') ?> explorer</a>
										<?php } ?>
									</td>
									<td class="mtime">-</td>
									<td class="size">-</td>
									<td></td>
								</tr><?php
                                foreach (scandir($f) as $entry) {
                                    if ( ($entry == '.') or ($entry == '..') ) continue;
                                    if ( is_dir($f.'/'.$entry) ) {
                                        $ext = '/';
                                        $modification = '-';
                                        $taille = '-';
                                    } else {
                                        $path_parts = pathinfo($entry);
                                        $ext = strtolower($path_parts['extension']);
                                        $stats = stat($f.'/'.$entry);
                                        $modification = date('Y-m-d H:i:s', $stats[9]);
                                        $taille = human_filesize($stats[7], 1);
                                    }
                                    if ( ! isset($extensions[$ext]) ) { $ext = NULL; }
                                    ?><tr data-entry="<?= $entry ?>" data-ext="<?= $ext ?>">
                                        <td class="icon">
											<?= icon($extensions[$ext]['icon']) ?>
                                        </td>
                                        <td class="name">
                                            <a href="<?= $path, $entry, ($ext=='/')?$ext:'' ?>"><?= $entry ?></a>
                                        </td>
                                        <td class="actions"><?php
											$a = $extensions[$ext]['actions'];
											for($i=0;$i<strlen($a); $i++) switch ($a[$i]) {
												case 'x': ?><a class="action" href="<?= $entry ?>/"><?= icon('folder-open-o') ?> explorer</a><?php break;
                                                case 's': ?><a class="action" href="<?= $entry ?>"><?= icon('code') ?> source</a><?php break;
                                                case 'S': ?><a class="action" href="<?= $entry ?>"><?= icon('code', 'verif') ?> source &amp; vérif.</a><?php break;
												case 'd': ?><a class="action" href="<?= $entry ?>?action=download"><?= icon('download') ?> télécharger</a><?php
												break;
												case 'i': ?><a class="action display" href="<?= $path, $entry ?>" title="<?= $entry ?>"><?= icon('eye') ?> visualiser</a><?php break;
                                                case 'C': ?><button class="action checkCSS"><?= icon('check') ?> vérifier</button><?php break;
                                                case 'H': ?><button class="action checkHTML"><?= icon('check') ?> vérifier</button><?php break;
                                            } ?>
                                        </td>
                                        <td class="mtime"><?= $modification ?></td>
                                        <td class="size"><?= $taille ?></td>
                                        <td><?php
                                            echo $extensions[$ext]['name'];
                                            if ( is_null($ext) ) {
                                                ?> [<a href="<?= $_SERVER['PHP_SELF'],'/',$entry ?>">essayer d'afficher</a>]<?php
                                            }
                                            if ( ($f == '.') && ($entry == $appName ) ) {
                                                echo ', ce script';
                                            }
                                        ?></td>
                                    </tr><?php
                                }
                            ?></tbody>
                        </table>
                    </section>
                    <section>
                        <h2><i class="fa fa-id-card-o"></i> Cookies actifs</h2>
                        <table id="cookieList">
                            <thead><tr><th>Nom</th><th>Valeur</th><th>Opérations</th></tr></thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td><input type="text" id="newCookieName"></td>
                                    <td><input type="text" id="newCookieValue"></td>
                                    <td>
                                        <button class="action add"><span class="fa fa-plus"></span> ajouter/modifier</button>
                                    </td>
                            </tfoot>
                        </table>
                    </section>
                    <section>
                        <h2><i class="fa fa-cogs"></i> Système</h2>
                        <table id="system">
                            <tbody>
                                <tr>
                                    <th>Cette application</th>
                                    <td>
                                        <code><?= $appName ?></code>,
										version <?= $appVersion ?>
                                        <a class="action" href="<?= $appURI, $appName ?>"><i class="fa fa-code"></i> source</a>
                                        <a class="action" href="<?= $appURI, $appName ?>?action=download"><i class="fa fa-download"></i> télécharger</a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Hôte</th>
                                    <td><?= php_uname() ?></td>
                                </tr>
                                <tr>
                                    <th>Serveur web</th>
                                    <td><?= $_SERVER["SERVER_SOFTWARE"] ?></td>
                                </tr>
                                <tr>
                                    <th>Interprêteur</th>
                                    <td>PHP/<?= PHP_VERSION ?> (<?= PHP_SAPI ?>)</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
				</main>
			<?php

			} else {
				// cas général : fichier
				$path_parts = pathinfo($f);
				$ext = strtolower($path_parts['extension']);
				if ($appAction == 'download') {
					ob_end_clean();
					header("Content-Disposition: attachment; filename=\"$f\"");
				} else {
                    if ($ext == 'php') {
                        // TODO ajouter liens ci-dessous: #source.NNN
                    }
                    ?><header><h1>Fichier&nbsp;: <code><?= $f ?></code></h1></header>
                    <main>
                        <?php
                            $hllines = [];
                            if ($ext == 'php') {
                                exec("php -l '$f'", $checkRepport, $checkCode);
                                ?><aside class="<?= $checkCode?'erreur':'OK' ?>"><pre><?php
                                foreach(array_filter($checkRepport) as $r) {
                                    if ( preg_match('/on line (\d+)$/', $r, $m) ) {
                                        $hllines[] = $m[1];
                                        $r = preg_replace('/line (\d+)$/', '<a href="#source.$1">line $1</a>', $r);
                                    }
                                    echo $r, "\n";
                                    ?></pre></aside><?php
                                }
                            }
                            ?>
                        <pre id="source" class="language-<?= $ext ?> line-numbers" data-line="<?= implode(',', $hllines) ?>"><code><?php
                }
				// contenu du fichier
				$code = file_get_contents($f, FALSE);
				// repérage des boucles
				if ($appAction != 'download') {
                    // 1er niveau de boucle
					$code = preg_replace_callback($motif_boucle, 'tague_boucle', htmlentities($code));
                    // éventuel 2e niveau de boucle
					$code = preg_replace_callback($motif_boucle, 'tague_boucle', $code);
				}
				// application des commentaires magiques
				$code = preg_replace_callback(
					'|/\*([\'"]?)(\w+)\(\*/(.*)/\*\)([\'"]?)\*/|U',
					function ($matches) {
						global $hooks;
						if ( ! isset($hooks[$matches[2]]) ) return($matches[1].$matches[3].$matches[4]);
						$hook = $hooks[$matches[2]];
						if ( isset($hook['sub']) ) return($matches[1].$hook['sub'].$matches[4]);
						return($matches[1].$hook['pre'].$matches[3].$hook['post'].$matches[4]);
					},
					$code
				);
				// affichage
				echo $code;
				if ($appAction == 'download') {
					exit;
				} else {
					?></code></pre></main><?php
				}
			}
            if ( is_dir($f) ) { // listing ?>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.11.1/baguetteBox.min.js" integrity="sha512-7KzSt4AJ9bLchXCRllnyYUDjfhO2IFEWSa+a5/3kPGQbr+swRTorHQfyADAhSlVHCs1bpFdB1447ZRzFyiiXsg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script>
                    function cookiesUpdateList() {
                        let cookieList = document.querySelector('#cookieList tbody');
                        cookieList.innerHTML = '';
                        let cookies = document.cookie.split('; ');
                        cookies.forEach(function(c){
                            let e = c.indexOf('=');
                            let n = c.substring(0, e);
                            let v = c.substring(e + 1);
                            let i = document.createElement('tr');
                            let d = document.createElement('td');
                                d.innerText = decodeURIComponent(n);
                                i.appendChild(d);
                                d.addEventListener('click', cookieName);
                            d = document.createElement('td');
                                d.innerText = decodeURIComponent(v);
                                i.appendChild(d);
                            d = document.createElement('td');
                                d.innerHTML = '<button class="action remove"><span class="fa fa-times"></span> supprimer</button>';
                                i.appendChild(d);
                                d.firstChild.addEventListener('click', cookieRemove);
                            cookieList.appendChild(i);
                        });
                    }
                    function cookieName() {
                        let n = this.parentNode.querySelector('td'); // élément td du nom
                        let i = document.querySelector('#cookieList tfoot input'); // nom de cookie à ajouter
                        i.value = n.innerText;
                    }
                    function cookieRemove(){
                        let n = this.parentNode.parentNode.querySelector('td'); // élément td du nom
                        document.cookie = n.innerText + '=;path=/;expires=Thu, 01 Jan 1970 00:00:00 GMT'; // suppression du cookie
                        cookiesUpdateList();
                    }
                    function cookieAdd() {
                        let c = this.parentNode.parentNode; // élément tr : cookie
                        let n = c.querySelector('td input').value; // nom
                        let v = c.querySelector('td:nth-of-type(2) input').value; // valeur
                        document.cookie = encodeURIComponent(n)+ '='+ encodeURIComponent(v)+ ';path=/';
                        cookiesUpdateList();
                    }
                    function checkCSS() {
                        let e = this.parentNode.parentNode; // élément tr : entrée
                        // nom du fichier
                        let f = e.querySelector('a').getAttribute('href');
                        // récupération du contenu
                        let xhr = new XMLHttpRequest();
                        if ( ! xhr ) return; // erreur de récupération du fichier
                        xhr.addEventListener('load', function(){
                            // insertion dans un formulaire créé à la volée
                            let t = document.createElement('div');
                            t.innerHTML = '<form method="POST" action="https://jigsaw.w3.org/css-validator/validator" enctype="multipart/form-data" target="_blank">\
                                <textarea name="text"></textarea>\
                                <input type="hidden" name="profile" value="css3svg">\
                                <input type="hidden" name="type" value="css">\
                                <input type="hidden" name="lang" value="fr"></form>';
                            t.querySelector('textarea').textContent = this.response;
                            document.body.append(t);
                            // soumission du formulaire, puis retrait de celui-ci
                            t.querySelector('form').submit();
                            t.remove();
                        });
                        xhr.open('GET', f);
                        xhr.send();
                    }
                    function checkHTML() {
                        let e = this.parentNode.parentNode;
                        let f = e.querySelector('a').getAttribute('href');
                        let xhr = new XMLHttpRequest();
                        if ( ! xhr ) return;
                        xhr.addEventListener('load', function(){
                            let t = document.createElement('div');
                            t.innerHTML = '<form method="POST" action="https://validator.w3.org/check" enctype="multipart/form-data" target="_blank">\
                                <textarea name="fragment"></textarea>\
                                <input type="hidden" name="prefill" value="0">\
                                <input type="hidden" name="showsource" value="yes">\
                                <input type="hidden" name="doctype" value="Inline">\
                                <button type="submit">envoyer</button></form>';
                            t.querySelector('textarea').textContent = this.response;
                            document.body.append(t);
                            t.querySelector('form').submit();
                            t.remove();
                        });
                        xhr.open('GET', f);
                        xhr.send();
                    }
                    document.addEventListener('DOMContentLoaded', function(){
                        cookiesUpdateList();
                        document.querySelector('#cookieList .add').addEventListener('click', cookieAdd);
                        baguetteBox.run('.display', {afterShow: function(){
                            let f = document.querySelector('#baguetteBox-overlay figure');
                            let i = f.querySelector('img');
                            if ((i.naturalWidth === undefined) || (i.naturalWidth == 0)) return;
                            let t = f.querySelector('figcaption');
                            t.innerText = t.innerText + ' — ' + i.naturalWidth + '×' + i.naturalHeight;
                        } });
                        document.querySelectorAll('button.checkCSS').forEach(function(b){
                            b.addEventListener('click', checkCSS);
                        });
                        document.querySelectorAll('button.checkHTML').forEach(function(b){
                            b.addEventListener('click', checkHTML);
                        });
                    });
                </script>

            <?php } else { // source ?>
                <footer>
                    <p>
                        <a class="action" href="<?= $currentURI ?>?action=download"><i class="fa fa-download"></i> télécharger ce fichier</a>
                        <a class="action" href="<?= $appURI, $appName ?>"><i class="fa fa-code"></i> source de <?= $appName ?></a>
                        <a class="action" href="<?= $appURI, $appName ?>?action=download"><i class="fa fa-download"></i> télécharger <?= $appName ?></a>
                    </p>
                </footer>

                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/prism.min.js" integrity="sha512-YBk7HhgDZvBxmtOfUdvX0z8IH2d10Hp3aEygaMNhtF8fSOvBZ16D/1bXZTJV6ndk/L/DlXxYStP8jrF77v2MIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/autoloader/prism-autoloader.min.js" integrity="sha512-zc7WDnCM3aom2EziyDIRAtQg1mVXLdILE09Bo+aE1xk0AM2c2cVLfSW9NrxE5tKTX44WBY0Z2HClZ05ur9vB6A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/components/prism-css-extras.min.js" integrity="sha512-JEJN8jMnX+Ryl2SPlM18/6TwaH5FnN+Mvasfnh3E7awC/JAVpuWOvc5rSMqCD7MM22x5PxQgRUr5h8G2zHceMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/previewers/prism-previewers.min.js" integrity="sha512-PuVg1LnCfceuiEI5m99TRYwcvy6DJMhcDXsE7xLa16tQzvQMThxrzKOYu8+ZJ0DCX9crnL588huU5UHspfQ2IA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/line-numbers/prism-line-numbers.min.js" integrity="sha512-br8H6OngKoLht57WKRU5jz3Vr0vF+Tw4G6yhNN2F3dSDheq4JiaasROPJB1wy7PxPk7kV/+5AIbmoZLxxx7Zow==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/inline-color/prism-inline-color.min.js" integrity="sha512-Lk0/glzAEUrLDkdda/X2w76WVQohbfJFe637QV4wuFdzex0objj1rtzIj78Vrt92pgbzL+C4eaMWhyzg42CyKw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/wpd/prism-wpd.min.js" integrity="sha512-s10ZG1dQcH/+QjQ7NCTPLeqIf4NmjngknaZKimUr4fnPi17GWUmQDB8LTcesbx2zyRxum3UmWZOdohsBiqOrLQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/keep-markup/prism-keep-markup.min.js" integrity="sha512-LC5nQYpThDWO3xsegzq9t+OQTcedwKX9ruWEaRsFS5xB1VfTWpOyIBHukVwxJPlNdLVA/Yy31OArNxs7SBrG8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/autolinker/prism-autolinker.min.js" integrity="sha512-/uypNVmpEQdCQLYz3mq7J2HPBpHkkg23FV4i7/WSUyEuTJrWJ2uZ3gXx1IBPUyB3qbIAY+AODbanXLkIar0NBQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/plugins/line-highlight/prism-line-highlight.min.js" integrity="sha512-MGMi0fbhnsk/a/9vCluWv3P4IOfHijjupSoVYEdke+QQyGBOAaXNXnwW6/IZSH7JLdknDf6FL6b57o+vnMg3Iw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script>
                    Prism.hooks.add('after-highlight', function(env) {
                        // on attend d’être dans la bonne itération de PrismJS
                        if ( env.grammar === undefined ) return;
                        // on parcourt toutes les boucles identifiées
                        env.element.querySelectorAll('span.boucle--debut').forEach(function(debut){
                            let contour = debut.parentNode.insertBefore(document.createElement('span'), debut);
                            console.debug('contour:', contour);
                            contour.classList.add('boucle');
                            contour.innerHTML = '<span>&nbsp;&nbsp;&nbsp;&nbsp;</span>';
                            let tl = contour.firstChild.offsetWidth; // largeur de 4 espaces
                            console.debug('tl:', tl);
                            console.debug('début:', debut);
                            let numBoucle = debut.dataset['boucle'];
                            let dl = Math.max(40, debut.offsetWidth); // largeur du début de boucle
                            let dh = debut.offsetHeight+2; // hauteur du début de boucle
                            let fin = env.element.querySelector(`span.boucle--fin[data-boucle="${numBoucle}"]`);
                            console.debug('fin:', fin);
                            let fl = Math.max(40, fin.offsetWidth);
                            let fh = fin.offsetHeight+2;
                            let fy = fin.getBoundingClientRect().y - debut.getBoundingClientRect().y;
                            let my = 4 + (dh+fy)/2;
                            console.debug('fy:', fy);
                            contour.innerHTML =
                                `<svg width="${Math.max(dl,fl)+20}" height="${dh+fy+fh+20}">
                                    <path d="
                                        M 3,6 l 3,-3 h 20 l 3,3 h 10 l 3,-3 h ${dl-30} l 3,3 v ${dh-3} l -3,3
                                        h -${dl-26-tl} l -3,3 h -10 l -3,-3 h -20 l -3,3
                                        v ${fy-dh-9} l 3,3
                                        h 20 l 3,3 h 10 l 3,-3 h ${fl-tl-26} l 3,3 v ${fh-3} l -3,3
                                        h -${fl-30} l -3,3 h -10 l -3,-3 h -20 l -3,-3
                                        L 3,6
                                    " />
                                    <text x="${tl-3}" y="${my}" text-anchor="middle" transform="rotate(-90 ${tl-3},${my})">boucle</text>
                                </svg>`;
                        });
						env.element.querySelectorAll('span.token.php').forEach(function(valeurPHP){
							// on ne garde que les écritures raccourcies
							let debut = valeurPHP.querySelector('.token.delimiter:not(:empty)');
							if ( (debut === null) || (debut.innerText.substr(2) != '=') ) return;
							console.debug('valeur PHP:', valeurPHP);
							// on évacue les cas avec saut de ligne
							if ( valeurPHP.innerText.search('\n') >= 0 ) return;
							// création d’un indicateur SVG
							console.debug('début:', debut);
							let contour = valeurPHP.insertBefore(document.createElement('span'), debut);
							console.debug('contour:', contour);
							contour.classList.add('valeur');
							let vl = valeurPHP.offsetWidth;
							let vh = valeurPHP.offsetHeight;
							console.debug('vl:', vl);
                            contour.innerHTML =
                                `<svg width="${vl+4}" height="${vh+4}">
                                    <path d="
                                        M 2,${vh/2+1} 6,2 ${vl-4},2 ${vl+2},${vh/2+1}
											${vl-2},${vh+2} 6,${vh+2} 2,${vh/2+1}
                                    " />
                                </svg>`;
						});
                    });
                </script>
            <?php } ?>
	</body>
</html>
