<?php
/*
 * @package     perfecttemplate
 * @copyright   2018 Perfect Web Team / perfectwebteam.nl
 * @license     GNU General Public License version 3 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

// Load Perfect Template Helper
require_once JPATH_THEMES . '/' . $this->template . '/helper.php';

// JLayout render
require_once JPATH_THEMES . '/' . $this->template . '/html/layouts/render.php';

// Helpers
// Create favicon and corresponding files on https://realfavicongenerator.net/
$favicolor           = '#da532c';
$favicolorBackground = '#ffffff';
PWTTemplateHelper::setMetadata($favicolor, $favicolorBackground);
PWTTemplateHelper::setFavicon($favicolor);
PWTTemplateHelper::unloadCss();
PWTTemplateHelper::unloadJs();
PWTTemplateHelper::loadCss();
PWTTemplateHelper::loadJs();
//PWTTemplateHelper::localstorageFont();

?>
<!DOCTYPE html>
<html class="html no-js" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head"/>
	<noscript>
		<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/noscript.css" rel="stylesheet" type="text/css"/>
	</noscript>
</head>

<body class="is-preload <?php echo PWTTemplateHelper::setBodyClass(); ?>">
<?php echo PWTTemplateHelper::setAnalytics(1, 'UA-5745474-6'); ?>
<!-- Wrapper -->
<div id="wrapper" class="divided">
	<jdoc:include type="message"/>
	<jdoc:include type="component"/>

	<!-- Footer -->
	<footer class="wrapper style1 align-center" id="stichting">
		<div class="inner">

			<div class="items style1 medium">
				<section>
					<jdoc:include type="modules" name="footer-left" style="none"/>
				</section>
				<section>
					<jdoc:include type="modules" name="footer-right" style="none"/>
				</section>
			</div>

			<jdoc:include type="modules" name="copyright" style="none"/>
		</div>
	</footer>
</div>
</body>
</html>
