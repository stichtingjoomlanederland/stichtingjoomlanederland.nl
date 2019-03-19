<?php
/**
 * @package     Perfecttemplate
 * @copyright   Copyright (c) 2019 Perfect Web Team / perfectwebteam.nl
 * @license     GNU General Public License version 3 or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Class PWTTemplateHelper
 *
 * @since 1.0.0
 */
class PWTTemplateHelper
{
	/**
	 * Method to get current Template Name
	 *
	 * @return string
	 * @throws Exception
	 * @since 1.0.0
	 */
	static public function template()
	{
		return Factory::getApplication()->getTemplate();
	}

	/**
	 * Method to get current Page Option
	 *
	 * @access public
	 *
	 * @return mixed
	 * @throws  Exception
	 * @since  1.0
	 */
	static public function getPageOption()
	{
		return Factory::getApplication()->input->getCmd('option', '');
	}

	/**
	 * Method to get current Page View
	 *
	 * @access public
	 *
	 * @return mixed
	 * @throws  Exception
	 * @since  1.0
	 */
	static public function getPageView()
	{
		return Factory::getApplication()->input->getCmd('view', '');
	}

	/**
	 * Method to get current Page Layout
	 *
	 * @access public
	 *
	 * @return mixed
	 * @throws  Exception
	 * @since  version
	 */
	static public function getPageLayout()
	{
		return Factory::getApplication()->input->getCmd('layout', '');
	}

	/**
	 * Method to get current Page Task
	 *
	 * @access public
	 *
	 * @return mixed
	 * @throws  Exception
	 * @since  1.0
	 */
	static public function getPageTask()
	{
		return Factory::getApplication()->input->getCmd('task', '');
	}

	/**
	 * Method to get the current Menu Item ID
	 *
	 * @access public
	 *
	 * @return integer
	 * @throws  Exception
	 * @since  1.0
	 */
	static public function getItemId()
	{
		return Factory::getApplication()->input->getInt('Itemid');
	}

	/**
	 * Method to get PageClass set with Menu Item
	 *
	 * @return mixed
	 * @throws  Exception
	 * @since  1.0
	 */
	static public function getPageClass()
	{
		$activeMenu = Factory::getApplication()->getMenu()->getActive();
		$pageClass  = ($activeMenu) ? $activeMenu->params->get('pageclass_sfx', '') : '';

		return $pageClass;
	}

	/**
	 * Method to determine whether the current page is the Joomla! homepage
	 *
	 * @access public
	 *
	 * @return boolean
	 * @throws  Exception
	 * @since  1.0
	 */
	static public function isHome()
	{
		// Fetch the active menu-item
		$activeMenu = Factory::getApplication()->getMenu()->getActive();

		// Return whether this active menu-item is home or not
		return (boolean) ($activeMenu) ? $activeMenu->home : false;
	}

	/**
	 * Method to fetch the current path
	 *
	 * @access public
	 *
	 * @param   string $output Output type
	 *
	 * @return mixed
	 * @since  1.0
	 */
	static public function getPath($output = 'array')
	{
		$path = Uri::getInstance()->getPath();
		$path = preg_replace('/^\//', '', $path);

		if ($output == 'array')
		{
			$path = explode('/', $path);

			return $path;
		}

		return $path;
	}

	/**
	 * Generate a list of useful CSS classes for the body
	 *
	 * @access public
	 *
	 * @return boolean
	 * @throws  Exception
	 * @since  1.0
	 */
	static public function setBodyClass()
	{
		$classes   = array();
		$classes[] = 'option-' . self::getPageOption();
		$classes[] = 'view-' . self::getPageView();
		$classes[] = self::getPageLayout() ? 'layout-' . self::getPageLayout() : 'no-layout';
		$classes[] = self::getPageTask() ? 'task-' . self::getPageTask() : 'no-task';
		$classes[] = 'itemid-' . self::getItemId();
		$classes[] = self::getPageClass();
		$classes[] = self::isHome() ? 'path-home' : 'path-' . implode('-', self::getPath('array'));

		return implode(' ', $classes);
	}

	/**
	 * Method to manually override the META-generator
	 *
	 * @access public
	 *
	 * @param   string $generator Generator tag in html source
	 *
	 * @return null
	 *
	 * @since  1.0
	 */
	static public function setGenerator($generator)
	{
		Factory::getDocument()->setGenerator($generator);

		return null;
	}

	/**
	 * Method to get the current sitename
	 *
	 * @access public
	 *
	 * @return string
	 * @since  1.0
	 */
	static public function getSitename()
	{
		return Factory::getConfig()->get('sitename');
	}

	/**
	 * Method to set some Meta data
	 *
	 * @access public
	 *
	 * @param   string $faviconColor           Color for Favicon
	 * @param   string $faviconColorBackground Color for Favicon Background
	 *
	 * @return void
	 * @throws Exception
	 * @since  1.0
	 */
	static public function setMetadata($faviconColor, $faviconColorBackground)
	{
		$doc = Factory::getDocument();

		$doc->setHtml5(true);
		$doc->setMetaData('X-UA-Compatible', 'IE=edge', true);
		$doc->setMetaData('viewport', 'width=device-width, initial-scale=1.0');
		$doc->setMetaData('mobile-web-app-capable', 'yes');
		$doc->setMetaData('apple-mobile-web-app-capable', 'yes');
		$doc->setMetaData('apple-mobile-web-app-status-bar-style', 'black');
		$doc->setMetaData('apple-mobile-web-app-title', self::getSitename());
		$doc->setMetaData('msapplication-TileColor', $faviconColor);
		$doc->setMetaData('msapplication-config', '/templates/' . self::template() . '/images/favicon/browserconfig.xml');
		$doc->setMetaData('theme-color', $faviconColorBackground);
		self::setGenerator(self::getSitename());
	}

	/**
	 * Method to set Favicon
	 *
	 * @param   string $faviconColor Color for Favicon
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   PerfectSite2.1.0
	 */
	static public function setFavicon($faviconColor)
	{
		$doc = Factory::getDocument();

		$doc->addHeadLink(
			'templates/' . self::template() . '/images/favicon/apple-touch-icon.png', 'apple-touch-icon', 'rel', array('sizes' => '180x180')
		);
		// $doc->addHeadLink('templates/' . self::template() . '/images/favicon/favicon-32x32.png', 'icon', 'rel', array('type' => 'image/png', 'sizes' => '32x32'));
		// $doc->addHeadLink('templates/' . self::template() . '/images/favicon/favicon-16x16.png', 'icon', 'rel', array('type' => 'image/png', 'sizes' => '16x16'));
		$doc->addHeadLink('templates/' . self::template() . '/images/favicon/site.webmanifest', 'manifest', 'rel');
		$doc->addHeadLink(
			'templates/' . self::template() . '/images/favicon/safari-pinned-tab.svg', 'mask-icon', 'rel', array('color' => $faviconColor)
		);
		$doc->addHeadLink('templates/' . self::template() . '/images/favicon/favicon.ico', 'shortcut icon', 'rel');
	}

	/**
	 * Method to get wether site is in development
	 *
	 * @access public
	 *
	 * @param   string $name Name of last word in site title
	 *
	 * @return string
	 * @since  PerfectSite2.1.0
	 */
	static public function isDevelopment($name = '[dev]')
	{
		return boolval(strpos(self::getSitename(), $name));
	}

	/**
	 * Method to determine whether the current page is the requested page
	 *
	 * @access public
	 *
	 * @param   string $request Requested page
	 *
	 * @return boolean
	 * @since  PerfectSite2.1.0
	 */
	static public function isPage($request = 'home')
	{
		return URI::getInstance()->getPath() == $request;
	}

	/**
	 * Remove unwanted CSS
	 *
	 * @return void
	 * @since  PerfectSite2.1.0
	 */
	static public function unloadCss()
	{
		$doc = Factory::getDocument();

		$unsetCss = array('com_finder');

		foreach ($doc->_styleSheets as $name => $style)
		{
			foreach ($unsetCss as $css)
			{
				if (strpos($name, $css) !== false)
				{
					unset($doc->_styleSheets[$name]);
				}
			}
		}
	}

	/**
	 * Load CSS
	 *
	 * @return void
	 * @throws Exception
	 * @since  PerfectSite2.1.0
	 */
	static public function loadCss()
	{
		HTMLHelper::_('stylesheet', 'style.css', ['version' => 'auto', 'relative' => true]);
	}

	/**
	 * Remove unwanted JS
	 *
	 * @return void
	 * @since  PerfectSite2.1.0
	 */
	static public function unloadJs()
	{
		$doc = Factory::getDocument();

		// Call JavaScript to be able to unset it correctly
		HTMLHelper::_('behavior.framework');
		HTMLHelper::_('bootstrap.framework');
		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('bootstrap.tooltip');

		// Unset unwanted JavaScript
		unset($doc->_scripts[$doc->baseurl . '/media/system/js/mootools-core.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/system/js/mootools-more.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/system/js/caption.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/system/js/core.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/jui/js/jquery.min.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/jui/js/jquery-noconflict.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/jui/js/jquery-migrate.min.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/jui/js/bootstrap.min.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/system/js/tabs-state.js']);
		unset($doc->_scripts[$doc->baseurl . '/media/system/js/validate.js']);

		if (isset($doc->_script['text/javascript']))
		{
			$doc->_script['text/javascript'] = preg_replace(
				'%jQuery\(window\)\.on\(\'load\'\,\s*function\(\)\s*\{\s*new\s*JCaption\(\'img.caption\'\);\s*}\s*\);\s*%', '',
				$doc->_script['text/javascript']
			);
			$doc->_script['text/javascript'] = preg_replace(
				'%\s*jQuery\(function\(\$\)\{\s*[initTooltips|initPopovers].*?\}\);\}\s*\}\);%', '', $doc->_script['text/javascript']
			);

			// Unset completly if empty
			if (empty($doc->_script['text/javascript']))
			{
				unset($doc->_script['text/javascript']);
			}
		}
	}

	/**
	 * Load JS
	 *
	 * @return void
	 * @since  PerfectSite2.1.0
	 */
	static public function loadJs()
	{
		HTMLHelper::_('script', 'jquery.min.js', ['version' => 'auto', 'relative' => true], ['defer' => 'true']);
		HTMLHelper::_('script', 'jquery.scrollex.min.js', ['version' => 'auto', 'relative' => true], ['defer' => 'true']);
		HTMLHelper::_('script', 'jquery.scrolly.min.js', ['version' => 'auto', 'relative' => true], ['defer' => 'true']);
		HTMLHelper::_('script', 'browser.min.js', ['version' => 'auto', 'relative' => true], ['defer' => 'true']);
		HTMLHelper::_('script', 'breakpoints.min.js', ['version' => 'auto', 'relative' => true], ['defer' => 'true']);
		HTMLHelper::_('script', 'util.js', ['version' => 'auto', 'relative' => true], ['defer' => 'true']);
		HTMLHelper::_('script', 'main.js', ['version' => 'auto', 'relative' => true], ['defer' => 'true']);
	}


	/**
	 * Load custom font in localstorage
	 *
	 * @return void
	 * @throws Exception
	 * @since  PerfectSite2.1.0
	 */
	static public function localstorageFont()
	{
		// Keep whitespace below for nicer source code
		$javascript
			= "    !function(){\"use strict\";function e(e,t,n){e.addEventListener?e.addEventListener(t,n,!1):e.attachEvent&&e.attachEvent(\"on\"+t,n)}function t(e){return window.localStorage&&localStorage.font_css_cache&&localStorage.font_css_cache_file===e}function n(){if(window.localStorage&&window.XMLHttpRequest)if(t(o))c(localStorage.font_css_cache);else{var n=new XMLHttpRequest;n.open(\"GET\",o,!0),e(n,\"load\",function(){4===n.readyState&&(c(n.responseText),localStorage.font_css_cache=n.responseText,localStorage.font_css_cache_file=o)}),n.send()}else{var a=document.createElement(\"link\");a.href=o,a.rel=\"stylesheet\",a.type=\"text/css\",document.getElementsByTagName(\"head\")[0].appendChild(a),document.cookie=\"font_css_cache\"}}function c(e){var t=document.createElement(\"style\");t.innerHTML=e,document.getElementsByTagName(\"head\")[0].appendChild(t)}var o=\"/templates/"
			. self::template()
			. "/css/font.css\";window.localStorage&&localStorage.font_css_cache||document.cookie.indexOf(\"font_css_cache\")>-1?n():e(window,\"load\",n)}();";
		Factory::getDocument()->addScriptDeclaration($javascript);
	}


	/**
	 * Ajax for SVG
	 *
	 * @return void
	 * @throws Exception
	 * @since  PerfectSite2.1.0
	 */
	static public function ajaxSVG()
	{
		$javascript = "var ajax=new XMLHttpRequest;ajax.open(\"GET\",\"" . Uri::Base() . "templates/" . self::template()
			. "/icons/icons.svg\",!0),ajax.send(),ajax.onload=function(a){var b=document.createElement(\"div\");b.className='svg-sprite';b.innerHTML=ajax.responseText,document.body.insertBefore(b,document.body.childNodes[0])};";
		Factory::getDocument()->addScriptDeclaration($javascript);
	}


	/**
	 * Method to detect a certain browser type
	 *
	 * @access public
	 *
	 * @param   string $shortname Shortname for browser
	 *
	 * @return string
	 * @since  PerfectSite2.1.0
	 */
	static public function isBrowser($shortname = 'ie6')
	{
		\JLoader::import('joomla.environment.browser');
		$browser = Browser::getInstance();

		switch ($shortname)
		{
			case 'edge':
				$rt = (stristr($browser->getAgentString(), 'edge')) ? true : false;
				break;
			case 'firefox':
			case 'ff':
				$rt = (stristr($browser->getAgentString(), 'firefox')) ? true : false;
				break;
			case 'ie':
				$rt = ($browser->getBrowser() == 'msie') ? true : false;
				break;
			case 'ie6':
				$rt = ($browser->getBrowser() == 'msie' && $browser->getVersion() == '6.0') ? true : false;
				break;
			case 'ie7':
				$rt = ($browser->getBrowser() == 'msie' && $browser->getVersion() == '7.0') ? true : false;
				break;
			case 'ie8':
				$rt = ($browser->getBrowser() == 'msie' && $browser->getVersion() == '8.0') ? true : false;
				break;
			case 'ie9':
				$rt = ($browser->getBrowser() == 'msie' && $browser->getVersion() == '9.0') ? true : false;
				break;
			case 'lteie9':
				$rt = ($browser->getBrowser() == 'msie' && $browser->getMajor() <= 9) ? true : false;
				break;
			default:
				$rt = (stristr($browser->getAgentString(), $shortname)) ? true : false;
				break;
		}

		return $rt;
	}

	/**
	 * Method to set Analytics
	 *
	 * @param   integer $analyticsType Number to indicate wich type of analytics to use
	 * @param   string  $analyticsId   Analytics ID
	 *
	 * @return string
	 * @since  1.0
	 */
	static public function setAnalytics($analyticsType = null, $analyticsId = null)
	{
		$doc        = Factory::getDocument();
		$bodyScript = '';

		if (!$analyticsType)
		{
			return false;
		}

		if (!$analyticsId)
		{
			return false;
		}

		switch ($analyticsType)
		{
			case 0:
				break;

			case 1:
				// Universal Google Universal Analytics - loaded in head
				HTMLHelper::_('script', '//www.googletagmanager.com/gtag/js?id=' . $analyticsId, array(), array('async' => 'async'));

				$headScript = "
<!-- Global site tag (gtag.js) - Google Analytics -->
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());

gtag('config', '" . $analyticsId . "');
<!-- End Global site tag (gtag.js) - Google Analytics -->
	  ";
				$doc->addScriptDeclaration($headScript);

				break;

			case 2:
				// Google Tag Manager - party loaded in head
				$headScript = "
  <!-- Google Tag Manager -->
  (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','"
					. $analyticsId . "');
  <!-- End Google Tag Manager -->

		  ";
				$doc->addScriptDeclaration($headScript);

				// Google Tag Manager - partly loaded directly after body
				$bodyScript = "<!-- Google Tag Manager -->
<noscript><iframe src=\"//www.googletagmanager.com/ns.html?id=" . $analyticsId . "\" height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager -->
";

				break;

			case 3:
				// Mixpanel.com - loaded in head
				$headScript = "
<!-- start Mixpanel -->(function(e,b){if(!b.__SV){var a,f,i,g;window.mixpanel=b;b._i=[];b.init=function(a,e,d){function f(b,h){var a=h.split(\".\");2==a.length&&(b=b[a[0]],h=a[1]);b[h]=function(){b.push([h].concat(Array.prototype.slice.call(arguments,0)))}}var c=b;\"undefined\"!==typeof d?c=b[d]=[]:d=\"mixpanel\";c.people=c.people||[];c.toString=function(b){var a=\"mixpanel\";\"mixpanel\"!==d&&(a+=\".\"+d);b||(a+=\" (stub)\");return a};c.people.toString=function(){return c.toString(1)+\".people (stub)\"};i=\"disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user\".split(\" \");
for(g=0;g<i.length;g++)f(c,i[g]);b._i.push([a,e,d])};b.__SV=1.2;a=e.createElement(\"script\");a.type=\"text/javascript\";a.async=!0;a.src=\"undefined\"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:\"file:\"===e.location.protocol&&\"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js\".match(/^\/\//)?\"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js\":\"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js\";f=e.getElementsByTagName(\"script\")[0];f.parentNode.insertBefore(a,f)}})(document,window.mixpanel||[]);
mixpanel.init(\"" . $analyticsId . "\");<!-- end Mixpanel -->
	  ";
				$doc->addScriptDeclaration($headScript);

				break;

			default:
				break;
		}

		return $bodyScript;
	}
}
