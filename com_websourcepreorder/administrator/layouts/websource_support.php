<?php

/**
 * Encart « accompagnement Websource » : layout unique réutilisable dans toutes les extensions Websource.
 *
 * Données attendues ($displayData) :
 *   slug    : identifiant de l'extension (ex. pkg_websourcepreorder), utilisé pour utm_source et ws_module
 *   version : version de l'extension (constante unique côté extension)
 *
 * Aucune requête externe : seuls des liens sortants (target=_blank) avec paramètres utm_* / ws_*.
 * Le choix « Masquer » est mémorisé 30 jours dans le localStorage du navigateur.
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var array $displayData */
$slug    = preg_replace('/[^a-z0-9_-]/i', '', (string) ($displayData['slug'] ?? ''));
$version = (string) ($displayData['version'] ?? '');
$root    = parse_url(Uri::root(), PHP_URL_SCHEME) . '://' . parse_url(Uri::root(), PHP_URL_HOST) . (parse_url(Uri::root(), PHP_URL_PORT) ? ':' . parse_url(Uri::root(), PHP_URL_PORT) : '');

$base = [
    'utm_source'   => $slug,
    'utm_medium'   => 'admin-extension',
    'utm_campaign' => 'accompagnement',
];
$tail = [
    'ws_module' => $slug,
    'ws_mv'     => $version,
    'ws_cms'    => 'joomla',
    'ws_cmsv'   => JVERSION,
    'ws_site'   => $root,
];
$contact = 'https://www.websource.fr/contact?' . http_build_query($base + $tail, '', '&', PHP_QUERY_RFC3986);
$rdv     = 'https://www.websource.fr/contact?' . http_build_query($base + ['utm_content' => 'rdv'] + $tail, '', '&', PHP_QUERY_RFC3986);
$id      = 'ws-support-' . $slug;
?>
<div class="card mt-4 ws-support" id="<?php echo $id; ?>" hidden>
	<div class="card-body">
		<div class="d-flex justify-content-between align-items-start">
			<h2 class="card-title h4 mb-2"><?php echo Text::_('WEBSOURCE_SUPPORT_TITLE'); ?></h2>
			<button type="button" class="btn btn-sm btn-outline-secondary" data-ws-support-hide><?php echo Text::_('WEBSOURCE_SUPPORT_HIDE'); ?></button>
		</div>
		<p class="mb-3"><?php echo Text::_('WEBSOURCE_SUPPORT_TEXT'); ?></p>
		<p class="mb-2">
			<a class="btn btn-primary" href="<?php echo htmlspecialchars($contact, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('WEBSOURCE_SUPPORT_CONTACT'); ?></a>
			<a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($rdv, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('WEBSOURCE_SUPPORT_APPOINTMENT'); ?></a>
		</p>
		<p class="small text-muted mb-0"><?php echo Text::_('WEBSOURCE_SUPPORT_PRIVACY'); ?></p>
	</div>
</div>
<script>
(function () {
  var box = document.getElementById(<?php echo json_encode($id); ?>), key = 'wsSupportHiddenUntil';
  if (!box) { return; }
  var hidden = false;
  try { hidden = parseInt(localStorage.getItem(key) || '0', 10) > Date.now(); } catch (e) {}
  if (!hidden) { box.hidden = false; }
  var btn = box.querySelector('[data-ws-support-hide]');
  if (btn) {
    btn.addEventListener('click', function () {
      box.hidden = true;
      try { localStorage.setItem(key, String(Date.now() + 30 * 24 * 3600 * 1000)); } catch (e) {}
    });
  }
})();
</script>
