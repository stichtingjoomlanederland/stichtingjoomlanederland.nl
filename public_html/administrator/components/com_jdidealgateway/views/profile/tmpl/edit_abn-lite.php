<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2019 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

/** @var JdidealgatewayViewProfile $this */
?>
<div class="span10">
	<?php echo $this->pspForm->renderFieldset('abn-lite'); ?>
</div>
<script type="text/javascript">
	function setTestserver(value)
	{
		if (value == '1')
		{
			document.adminForm.jform_merchantId.value = 'TESTiDEALEASY';
		}
		else
		{
			document.adminForm.jform_merchantId.value = '';
		}
	}
</script>
