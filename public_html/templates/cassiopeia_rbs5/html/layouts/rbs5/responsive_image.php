<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Params in object
 * image			: string
 * alt				: string or empty
 * resolution		: array ('resolution', '450x350')
 *
 * More information: https://github.com/joomla-framework/image
 * Joomla Image class: https://api.joomla.org/cms-3/classes/Joomla.CMS.Image.Image.html
 *
 * // Basic implementation in override
 *	<?php echo Joomla\CMS\Layout\LayoutHelper::render(
 *		'rbs5.responsive_image',
 *		[
 *			'src' => 'images/testimg.jpg',
 *			'class' => 'r2h-img-class',
 *			'alt' => '', // Can be filled
 *			'sizes' => ['1200x900', '800x600', '400x300'], // 4:3
 *		]
 *	); ?>
 *
 */

defined('_JEXEC') or die;

use Joomla\CMS\Image\Image;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

// Layout options
(array) $sizes		= $displayData['sizes']; // ['400x400','200x200']

$img = HTMLHelper::_('cleanImageURL', $displayData['src']);

// Get the server path info from the image
$serverImagePath 	= JPATH_ROOT . '/' . $img->url;

// Check if image is present in the folder structure
if (!file_exists($serverImagePath))
{
    echo 'File does not exist or is moved';
    return;
}

// Get the image class
if(isset($displayData['class']) && !empty($displayData['class'])) {
	$class = $displayData['class'];
} else {
	$class = 'responsive-image';
}

// Get the current image info
$fileInfo 			= pathinfo($serverImagePath);
$filebasename		= $fileInfo['basename']; // foo.jpg
$filename			= $fileInfo['filename']; // foo
$fileExtension		= $fileInfo['extension']; // jpg
$serverPath 		= $fileInfo['dirname'] . '/'; // E:\wamp64\www\testsite/images/sampledata/cassiopeia
$thumbnailPath		= $fileInfo['dirname'] . '/thumbs/'; // E:\wamp64\www\testsite/images/sampledata/cassiopeia/thumbs

// Use Cache folder for create thumbs in thumbs folder

// $relImgPathArr		= pathinfo($img->url);
// $relImgPath			= URI::root(true) . '/' . $relImgPathArr['dirname'] . '/thumbs/';


// Use Cache folder for create thumbs in thumbs folder
$thumbnailPath		= JPATH_ROOT . '/cache/thumbs/'; // E:\wamp64\www\testsite/images/sampledata/cassiopeia/thumbs
$relImgPath			= URI::root(true) . '/cache/thumbs/';

$img->url = $relImgPath . $filebasename;

$count 				= count($sizes);

// Check extensions
$acceptedExt 		= ['jpg','JPG','jpeg','JPEG','png','PNG'];

if (!in_array($fileExtension, $acceptedExt)) {
	echo 'Wrong image extension. Use JPG or PNG';
	return;
}

// Create folder if not exists
if (!Folder::exists($thumbnailPath)) {
	Folder::create($thumbnailPath);
}

// include the URL of the uploaded image that you want to resize
$image 				= new Image($serverPath . $filebasename);

$resonsiveImgObject = [];
$count 				= count($sizes);

foreach ($sizes as $index => $size) {

	$separateSizes 	= explode('x', $size);

	$newImgWidth 	= $separateSizes[0];
	$newImgHeight 	= $separateSizes[1];

	// Server path to the image
	$serverThumbPath = $thumbnailPath . $filename . '-' . $size . '.' . $fileExtension;

	// Create file if not present
	if(!File::exists($serverThumbPath)) {

		// Resize and create thumbnail
		$image->cropResize($newImgWidth, $newImgHeight, true)->toFile($serverThumbPath);
	}

	// Relative path to the image
	$thumbnail = $relImgPath . $filename . '-' . $size . '.' . $fileExtension;

	// Fill the araay with elements
	$resonsiveImgObject[$index]['width']  = $newImgWidth;
	$resonsiveImgObject[$index]['height'] = $newImgHeight;
	$resonsiveImgObject[$index]['url'] = $thumbnail;
}

// Sort array on the width
usort($resonsiveImgObject, function ($a, $b): int {
    return $a['width'] <=> $b['width'];
});

if (isset($displayData['alt']) && !empty($displayData['alt'])) {
	$altText = 'alt="' . $displayData['alt'] . '"';
} else {
	$altText = 'role="presentation"';
}
?>
<picture>
<?php foreach($resonsiveImgObject as $imgindex => $img) : ?>
	<?php if (!--$count <= 0) : ?>
		<source media="(max-width:<?php echo $img['width']; ?>px)" srcset="<?php echo $img['url']; ?>">
    <?php else : ?>
		<img
			src="<?php echo $img['url']; ?>"
			width="<?php echo $resonsiveImgObject[$imgindex]['width']; ?>"
			height="<?php echo $resonsiveImgObject[$imgindex]['height']; ?>"
			<?php echo $altText; ?>
			class="<?php echo $class; ?>"
			loading="lazy">
	<?php endif; ?>
<?php endforeach; ?>
</picture>
