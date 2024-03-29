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

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;

/**
 * RO Payments payment Button.
 *
 * @since  1.0.0
 */
class PlgButtonJdidealpaymentbutton extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application
	 *
	 * @var    SiteApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Display the button.
	 *
	 * @param   string  $name  The name of the button to display.
	 *
	 * @return  object|void The button to show or nothing if we are on front-end.
	 *
	 * @since   1.0.0
	 */
	public function onDisplay($name)
	{
		// Do not show the button on the front-end as we have no front-end support
		if ($this->app->isClient('site'))
		{
			return;
		}

		$link
			= 'index.php?option=com_jdidealgateway&amp;view=jdidealgateway&amp;layout=button&amp;tmpl=component&amp;'
			. Session::getFormToken() . '=1&amp;editor=' . $name;

		$button = new CMSObject;
		$button->set('modal', true);
		$button->set('class', 'btn');
		$button->set('link', $link);
		$button->set('text', Text::_('PLG_JDIDEALPAYMENTBUTTON_BUTTON'));
		$button->set('name', 'jdideal');
		$button->set(
			'iconSVG', <<<SVG
		<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   xmlns:xlink="http://www.w3.org/1999/xlink"
   viewBox="0 0 32 32"
   height="32"
   width="32"
   version="1.1"
   id="svg4146">
  <metadata
     id="metadata4152">
    <rdf:RDF>
      <cc:Work
         rdf:about="">
        <dc:format>image/svg+xml</dc:format>
        <dc:type
           rdf:resource="http://purl.org/dc/dcmitype/StillImage" />
        <dc:title></dc:title>
      </cc:Work>
    </rdf:RDF>
  </metadata>
  <defs
     id="defs4150" />
  <image
     y="0"
     x="0"
     id="image4154"
     xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJ
bWFnZVJlYWR5ccllPAAAA2hpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdp
bj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6
eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0
NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJo
dHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlw
dGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEu
MC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVz
b3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1N
Ok9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDpEOTVDM0U0MzExMjA2ODExODIyQUJFMzNBQjk1
OTM3MiIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpDM0RCNzVCQjhFQ0MxMUUyOTUxREQxQ0RF
MkIwQjdERSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpDM0RCNzVCQThFQ0MxMUUyOTUxREQx
Q0RFMkIwQjdERSIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRv
c2gpIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RkVBQkNB
M0MyNDIwNjgxMTgyMkFCRTMzQUI5NTkzNzIiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RDk1
QzNFNDMxMTIwNjgxMTgyMkFCRTMzQUI5NTkzNzIiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRm
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
"
     preserveAspectRatio="none"
     height="32"
     width="32" />
</svg>
SVG
		);

		$button->set(
			'options', [
				'height' => '500px',
				'width' => '350px',
				'bodyHeight' => '70',
				'modalWidth' => '30',
			]
		);

		if (JVERSION < 4)
		{
			$button->set(
				'options', "{handler: 'iframe', size: {x: 500, y: 350}}"
			);
		}

		return $button;
	}
}
