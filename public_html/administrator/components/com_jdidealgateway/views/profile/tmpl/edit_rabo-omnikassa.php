<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2019 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var JdidealgatewayViewProfile $this */
?>
<div class="span10">
	<?php echo $this->pspForm->renderFieldset('rabo-omnikassa'); ?>
</div>
<div class="span2">
	<table class="table table-striped">
		<caption><?php echo Text::_('COM_JDIDEALGATEWAY_DASHBOARD_LINKS')?></caption>
		<thead><tr><th><?php echo Text::_('COM_JDIDEALGATEWAY_PRODUCTION_DASHBOARD'); ?></th><th><?php echo Text::_('COM_JDIDEALGATEWAY_TEST_DASHBOARD'); ?></th></tr></thead>
		<tfoot><tr><td></td><td></td></tr></tfoot>
		<tbody>
			<tr>
				<td class="center"><?php echo HTMLHelper::_('link', 'https://download.omnikassa.rabobank.nl/', HTMLHelper::_('image', 'com_jdidealgateway/rabobank.jpg', 'Rabobank', false, true), 'target="_new"'); ?></td>
				<td class="center"></td>
			</tr>
		</tbody>
	</table>
</div>
<script type="text/javascript">
	function setTestserver(value)
	{
		if (value == '1')
		{
			document.adminForm.jform_merchantId.value = '002020000000001';
			document.adminForm.jform_password.value = '002020000000001_KEY1';
		}
		else
		{
			document.adminForm.jform_merchantId.value = '';
			document.adminForm.jform_password.value = '';
		}
	}
</script>
