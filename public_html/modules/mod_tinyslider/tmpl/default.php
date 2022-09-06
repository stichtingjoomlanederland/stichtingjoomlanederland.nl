<?php
/**
 * @copyright	Copyright (c) 2021 R2H (https://www.r2h.nl). All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;
use R2H\Module\TinySlider\Site\Helper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

// Load CSS
$wa->registerAndUseStyle('tinyslider.css', 'mod_tinyslider/tiny-slider.min.css', [], ['as'=>'style']);

// Load JS
$wa->registerAndUseScript('tinyslider.js', 'mod_tinyslider/tiny-slider.min.js', [], ['as'=>'script']);

// Load JS inline
$script = '
window.addEventListener("load", () => {
	var slider = tns({
		"container": ".my-slider",
		"items": 1.3,
		"mouseDrag": true,
		"controlsPosition": "bottom",
		"arrowKeys": "true",
		"autoplay": true,
		"autoplayTimeout": 2000,
		"autoplayButtonOutput": false,
		"controlsText": [
			"<i class=\"fas fa-2x fa-arrow-alt-circle-left\"></i>",
			"<i class=\"fas fa-2x fa-arrow-alt-circle-right\"></i>"
		],
		"nav": false,
		"responsive": {
			640: {items: 2.3},
			700: {},
			900: {items: 3.3}
		}
		});
	})
';
$wa->addInlineScript($script, ['name' => 'tinyslider' . $module->id]);
?>

<div class="my-slider">
	<?php foreach ($slides as $k => $slide) : ?>

		<?php if (!empty($slide->image)) : ?>
		<div class="position-relative">
		<?php echo Joomla\CMS\Layout\LayoutHelper::render('rbs5.responsive_image',[
			'src' => $slide->image,
			'class' => 'r2h-img-class img-fluid',
			'alt' => $slide->title, // Can be filled
			'sizes' => ['800x600', '600x450', '400x300'], // 4:3
		]); ?>
		<div class="my-slider__title position-absolute"><?php echo $slide->title; ?></div>
		</div>
		<?php endif; ?>
	<?php endforeach; ?>
</div>