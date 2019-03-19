<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Create a shortcut for params.
$params = $this->item->params;

// Get images & links
$images = json_decode($this->item->images);
$urls   = json_decode($this->item->urls);
?>

<?php if ($this->item->id == 1): ?>
<section class="banner style1 orient-left content-align-left image-position-right fullscreen onload-image-fade-in onload-content-fade-right">
	<?php else: ?>
	<section class="spotlight style1 orient-<?php echo $this->item->align; ?> content-align-left image-position-center onscroll-image-fade-in">
		<?php endif; ?>

		<div class="content">
			<?php if ($params->get('show_title')) : ?>
				<header>
					<h2><?php echo $this->escape($this->item->title); ?></h2>
					<?php if (isset($this->item->jcfields[1])): ?>
						<p><?php echo $this->item->jcfields[1]->value ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<?php echo $this->item->text; ?>

			<?php if ($urls->urla && $urls->urlatext): ?>
				<ul class="actions stacked">
					<li>
						<a href="<?php echo $urls->urla; ?>" class="button"><?php echo $urls->urlatext; ?></a>
					</li>
				</ul>
			<?php endif; ?>
		</div>
		<div class="image">
			<?php if (isset($images->image_intro) && !empty($images->image_intro)) : ?>
				<img src="<?php echo htmlspecialchars($images->image_intro, ENT_COMPAT, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($images->image_intro_alt, ENT_COMPAT, 'UTF-8'); ?>"/>
			<?php endif; ?>
		</div>

	</section>