<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

?>
<div id="backup-percentage" class="akeeba-progress">
	<div id="progressbar-inner" class="akeeba-progress-fill" style="width: <?php echo (int) $this->percent; ?>%"></div>
    <div class="akeeba-progress-status">
	    <?php echo (int) $this->percent; ?>%
    </div>
</div>

<div class="akeeba-panel--information">
	<p>
		<?php echo \JText::sprintf('COM_AKEEBA_REMOTEFILES_LBL_DOWNLOADEDSOFAR', $this->done, $this->total, $this->percent); ?>
    </p>
</div>
