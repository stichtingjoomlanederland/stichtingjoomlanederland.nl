<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var JdidealgatewayViewProfile $this */

$file = JPATH_LIBRARIES . '/Jdideal/Psp/Ing/Connector/config.conf';
?>
<div class="span10">
	<?php
	echo $this->pspForm->renderFieldset('advanced'); ?>

	<?php
	if (File::exists($file)) : ?>
		<fieldset class="adminform">
			<legend><?php
				echo Text::_('COM_ROPAYMENTS_IDEAL_CERT_FILES'); ?></legend>
			<?php
			$config = parse_ini_file($file); ?>
			<div>
				<?php
				if (File::exists($config['PRIVATECERT']))
				{
					echo HTMLHelper::_('image', 'com_jdidealgateway/tick.png', Text::_('COM_ROPAYMENTS_CERT_IS_FOUND'),
						['title' => Text::_('COM_ROPAYMENTS_CERT_IS_FOUND')], true);
				}
				else
				{
					echo HTMLHelper::_('image', 'com_jdidealgateway/cross.png',
						Text::_('COM_ROPAYMENTS_CERT_NOT_FOUND'),
						['title' => Text::_('COM_ROPAYMENTS_CERT_NOT_FOUND')], true);
					echo '<div class="img-middle">' . Text::_('COM_ROPAYMENTS_CERT_NOT_FOUND') . '</div>';
				}

				echo '<div class="img-middle">' . $config['PRIVATECERT'] . '</div>';
				?>
			</div>
			<div class="clr"></div>
			<div>
				<?php
				if (File::exists($config['PRIVATEKEY']))
				{
					echo HTMLHelper::_('image', 'com_jdidealgateway/tick.png', Text::_('COM_ROPAYMENTS_PRIV_IS_FOUND'),
						['title' => Text::_('COM_ROPAYMENTS_PRIV_IS_FOUND')], true);
				}
				else
				{
					echo HTMLHelper::_('image', 'com_jdidealgateway/cross.png',
						Text::_('COM_ROPAYMENTS_PRIV_NOT_FOUND'),
						['title' => Text::_('COM_ROPAYMENTS_PRIV_NOT_FOUND')], true);
					echo '<div class="img-middle">' . Text::_('COM_ROPAYMENTS_PRIV_NOT_FOUND') . '</div>';
				}

				echo '<div class="img-middle">' . $config['PRIVATEKEY'] . '</div>';
				?>
			</div>
		</fieldset>
		<div class="clr"></div>
	<?php
	endif; ?>
	<fieldset class="adminform">
		<legend><?php
			echo Text::_('COM_ROPAYMENTS_IDEAL_CERT_UPLOAD'); ?></legend>
		<table class="adminlist">
			<tbody>
			<tr>
				<td>
					<?php
					echo Text::_('COM_ROPAYMENTS_UPLOAD_CERT_FILE'); ?>
				</td>
				<td>
					<?php
					echo $this->pspForm->getInput('cert_upload', 'certificate'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php
					echo Text::_('COM_ROPAYMENTS_UPLOAD_PRIV_FILE'); ?>
				</td>
				<td>
					<?php
					echo $this->pspForm->getInput('priv_upload', 'certificate'); ?>
				</td>
			</tr>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="2"><span class="certificate_warning"><?php
						echo Text::_('COM_ROPAYMENTS_CERT_WILL_OVERRRIDE'); ?></span></td>
			</tr>
			</tfoot>
		</table>
	</fieldset>
</div>
<div class="span2">
	<table class="table table-striped">
		<caption><?php
			echo Text::_('COM_ROPAYMENTS_DASHBOARD_LINKS') ?></caption>
		<thead>
		<tr>
			<th><?php
				echo Text::_('COM_ROPAYMENTS_PRODUCTION_DASHBOARD'); ?></th>
			<th><?php
				echo Text::_('COM_ROPAYMENTS_TEST_DASHBOARD'); ?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td colspan="2"></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td class="center"><?php
				echo HTMLHelper::_('link', 'https://ideal-portal.ing.nl',
					HTMLHelper::_('image', 'com_jdidealgateway/ing.jpg', 'ING', false, true), 'target="_new"'); ?></td>
			<td class="center"><?php
				echo HTMLHelper::_('link', 'https://sandbox.ideal-portal.ing.nl',
					HTMLHelper::_('image', 'com_jdidealgateway/ing.jpg', 'ING', false, true), 'target="_new"'); ?></td>
		</tr>
		</tbody>
	</table>
</div>
