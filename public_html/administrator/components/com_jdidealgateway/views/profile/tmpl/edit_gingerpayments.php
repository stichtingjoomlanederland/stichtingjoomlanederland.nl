<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

/** @var JdidealgatewayViewProfile $this */
?>
<div class="span10">
	<?php
	foreach ($this->pspForm->getFieldset('gingerpayments') as $field) : ?>
		<?php
		if ($field->getAttribute('name') === 'payment') : ?>
			<joomla-field-fancy-select>
				<?php
				echo $field->renderField(); ?>
			</joomla-field-fancy-select>
		<?php
		else : ?>
			<?php
			echo $field->renderField(); ?>
		<?php
		endif; ?>
	<?php
	endforeach; ?>
</div>
