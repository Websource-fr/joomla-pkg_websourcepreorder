<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 *
 * Layout surchargeable : templates/<template>/html/layouts/com_websourcepreorder/
cart_notice.php
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div class="wspo-cart-notice">
	<strong><?php echo Text::_('COM_WEBSOURCEPREORDER_CART_NOTICE'); ?></strong>
	<ul>
		<?php foreach ($displayData['lines'] as $l) : ?>
			<li><?php echo $e($l['name']); ?><?php echo $l['release_date'] !== '' ? ' — ' . $e(Text::_('COM_WEBSOURCEPREORDER_RELEASE') . ' ' . $l['release_date']) : ''; ?></li>
		<?php endforeach; ?>
	</ul>
</div>
