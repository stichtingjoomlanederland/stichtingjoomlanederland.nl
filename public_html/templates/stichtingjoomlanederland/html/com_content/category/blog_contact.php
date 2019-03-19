<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<section class="wrapper style1 align-center">
	<div class="inner medium">
		<h2><?php echo $this->escape($this->item->title); ?></h2>
		<?php echo $this->item->text; ?>
	</div>
</section>