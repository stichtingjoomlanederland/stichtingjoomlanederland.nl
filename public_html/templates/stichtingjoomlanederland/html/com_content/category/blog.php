<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

?>

<?php $leadingcount = 1; ?>
<?php if (!empty($this->lead_items)) : ?>
	<?php foreach ($this->lead_items as &$item) : ?>
		<?php
		$item->align = ($leadingcount % 2 == 0) ? 'right' : 'left';
		$this->item  = &$item;

		switch ($this->item->id)
		{
			case 7:
				echo $this->loadTemplate('slider');
				break;
			case 8:
				echo $this->loadTemplate('bestuur');
				break;
			case 9:
				echo $this->loadTemplate('contact');
				break;
			default:
				echo $this->loadTemplate('item');
				break;
		}
		?>
		<?php $leadingcount++; ?>
	<?php endforeach; ?>
<?php endif; ?>