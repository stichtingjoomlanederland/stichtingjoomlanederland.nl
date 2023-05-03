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

use Joomla\CMS\Language\Text;

/** @var JdidealgatewayViewEmails $this */
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal"><?php echo Text::_('COM_ROPAYMENTS_MODAL_CLOSE'); ?></button>
<button type="button" class="btn btn-success logCopy" data-id="<?php echo $this->current; ?>">
	<?php echo Text::_('COM_ROPAYMENTS_COPY_LOG'); ?>
</button>

