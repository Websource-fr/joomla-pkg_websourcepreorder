<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 *
 * Layout surchargeable : templates/<template>/html/layouts/com_websourcepreorder/
account_block.php
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Websource\Component\Websourcepreorder\Administrator\Service\DisplayHelper;
use Websource\Component\Websourcepreorder\Administrator\Service\Urls;

/** @var array $displayData */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<aside class="wspo-account-block">
	<h3><?php echo $e($displayData['title']); ?></h3>
	<?php if (!empty($displayData['items'])) : ?>
		<ul class="wspo-account-block__items">
			<?php foreach ($displayData['items'] as $it) : ?>
				<li><a href="<?php echo $e($it['url']); ?>">
					<?php if ($it['image'] !== '') : ?><img src="<?php echo $e($it['image']); ?>" alt="" width="56" height="56" loading="lazy"><?php endif; ?>
					<span><strong><?php echo $e($it['name']); ?></strong><br><small><?php echo $it['release_date'] !== '' ? $e(Text::_('COM_WEBSOURCEPREORDER_RELEASE_ON') . ' ' . $it['release_date']) : $e(Text::_('COM_WEBSOURCEPREORDER_RELEASE_SOON')); ?></small></span>
				</a></li>
			<?php endforeach; ?>
		</ul>
		<p><a class="wspo-link" href="<?php echo $e(Urls::listPage()); ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_SEE_ALL'); ?></a></p>
	<?php endif; ?>
	<?php if (!empty($displayData['form'])) : ?>
		<?php echo DisplayHelper::layout('subscribe', ['place' => $displayData['place']]); ?>
	<?php endif; ?>
</aside>
