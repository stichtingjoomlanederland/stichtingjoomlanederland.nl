<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

class RSFormProAssets
{
	// This flag checks if we need to run after the onAfterRender() event.
	public static $replace = false;
	
	public static $scripts 			= array();
	public static $inlineScripts 	= '';
	public static $styles 			= array();
	public static $inlineStyles 	= '';
	public static $customTags 		= '';

	// Holds the resources already loaded (CSS and JS) so they will not be loaded twice by the System Plugin
	public static $added = array();
	
	public static function addScript($path) {
		if (self::$replace) {
			self::$scripts[$path] = 1;
		} else {
			if (method_exists(JFactory::getDocument(), 'addScript')) {
				self::$added[$path] = 1;
				JFactory::getDocument()->addScript($path, array('version' => 'auto'));
			}
		}
	}
	
	public static function addScriptDeclaration($script) {
		if (self::$replace) {
			if ($script) {
				self::$inlineScripts .= chr(13).$script;
			}
		} else {
			if (method_exists(JFactory::getDocument(), 'addScriptDeclaration')) {
				JFactory::getDocument()->addScriptDeclaration($script);
			}
		}
	}
	
	public static function addStyleSheet($path) {
		if (self::$replace) {
			self::$styles[$path] = 1;
		} else {
			if (method_exists(JFactory::getDocument(), 'addStyleSheet')) {
				self::$added[$path] = 1;
				JFactory::getDocument()->addStyleSheet($path, array('version' => 'auto'));
			}
		}
	}
	
	public static function addStyleDeclaration($style) {
		if (self::$replace) {
			if ($style) {
				self::$inlineStyles .= chr(13).$style;
			}
		} else {
			if (method_exists(JFactory::getDocument(), 'addStyleDeclaration')) {
				JFactory::getDocument()->addStyleDeclaration($style);
			}
		}
	}
	
	public static function addCustomTag($custom) {
		if (self::$replace) {
			if ($custom) {
				self::$customTags .= chr(13).$custom;
			}
		} else {
			if (method_exists(JFactory::getDocument(), 'addCustomTag')) {
				JFactory::getDocument()->addCustomTag($custom);
			}
		}
	}
	
	public static function render() {
		if (self::$replace) {
			$body 		= self::getBody();
			$newHead 	= '';
			
			if (self::$scripts) {
				foreach (self::$scripts as $src => $tmp) {
					if (!isset(self::$added[$src])) {
						$test = self::createScript($src, true);

						if (strpos($body, $test) === false)
						{
							$script = self::createScript($src);
							$newHead .= $script;
						}
					}
				}
				// Reset
				self::$scripts = array();
			}

			if (self::$styles) {
				foreach (self::$styles as $src => $tmp) {
					if (!isset(self::$added[$src])) {
						$test = self::createStyleSheet($src, true);

						if (strpos($body, $test) === false)
						{
							$style = self::createStyleSheet($src);
							$newHead .= $style;
						}
					}
				}
				// Reset
				self::$styles = array();
			}
			
			if (self::$inlineStyles) {
				$inlineStyle = self::createStyleDeclaration(self::$inlineStyles);
				if (strpos($body, $inlineStyle) === false)
				{
					$newHead .= $inlineStyle;
				}
				// Reset
				self::$inlineStyles = '';
			}
			
			if (self::$inlineScripts) {
				$inlineScript = self::createScriptDeclaration(self::$inlineScripts);
				if (strpos($body, $inlineScript) === false)
				{
					$newHead .= $inlineScript;
				}
				// Reset
				self::$inlineScripts = '';
			}
			
			if (self::$customTags) {
				$customTag = self::$customTags."\n";
				if (strpos($body, $customTag) === false)
				{
					$newHead .= $customTag;
				}
				// Reset
				self::$customTags = '';
			}
			
			if ($newHead) {
				$body = str_replace('</head>', $newHead.'</head>', $body);
				self::setBody($body);
			}
		}
	}

	protected static function getBody() {
		return JFactory::getApplication()->getBody();
	}

	protected static function setBody($body) {
		return JFactory::getApplication()->setBody($body);
	}

	protected static function isHTML5() {
		static $result;
		if ($result === null) {
			if (is_callable(array(JFactory::getDocument(), 'isHtml5'))) {
				$result = JFactory::getDocument()->isHtml5();
			} else {
				$result = false;
			}
		}

		return $result;
	}

	protected static function createScript($src, $test = false)
	{
		$srcLine = ' src="' . $src;

		if (!$test)
		{
			$html = '<script';

			if (!self::isHTML5())
			{
				$html .= ' type="text/javascript"';
			}

			$html .= $srcLine;
			$html .= '"></script>' . "\n";
		}
		else
		{
			$html = $srcLine;
		}

		return $html;
	}

	protected static function createScriptDeclaration($inlineScripts) {
		$html = '<script';
		if (!self::isHTML5()) {
			$html .= ' type="text/javascript"';
		}
		$html .= '>'. "\n". $inlineScripts. "\n". '</script>'. "\n";

		return $html;
	}

	protected static function createStyleSheet($src, $test = false)
	{
		$srcLine = ' href="' . $src;

		if (!$test)
		{
			$html = '<link rel="stylesheet"';

			if (!self::isHTML5())
			{
				$html .= ' type="text/css"';
			}

			$html .= $srcLine;
			$html .= '" />' . "\n";
		}
		else
		{
			$html = $srcLine;
		}

		return $html;
	}

	protected static function createStyleDeclaration($inlineStyles) {
		$html = '<style';
		if (!self::isHTML5()) {
			$html .= ' type="text/css"';
		}
		$html .= '>'. "\n". $inlineStyles. "\n". '</style>'. "\n";

		return $html;
	}

	public static function addJquery()
	{
		try
		{
			JHtml::_('jquery.framework');

			// This allows jQuery to be loaded after content has been rendered in Joomla! 3.x
			if (version_compare(JVERSION, '4.0', '<') && static::$replace)
			{
				$debug = (boolean) JFactory::getConfig()->get('debug');

				static::addScript(JHtml::_('script', 'jui/jquery.min.js', array('version' => 'auto', 'relative' => true, 'detectDebug' => $debug, 'pathOnly' => true)));
				static::addScript(JHtml::_('script', 'jui/jquery-noconflict.js', array('version' => 'auto', 'relative' => true, 'pathOnly' => true)));
				static::addScript(JHtml::_('script', 'jui/jquery-migrate.min.js', array('version' => 'auto', 'relative' => true, 'detectDebug' => $debug, 'pathOnly' => true)));
			}
		}
		catch (Exception $e)
		{
			// Let's try to add the asset through our own function on Joomla! 4.x
			if (version_compare(JVERSION, '4.0', '>=') && static::$replace)
			{
				try
				{
					static::addScript(JFactory::getApplication()->getDocument()->getWebAssetManager()->getAsset('script', 'jquery')->getUri());
				}
				catch (Exception $assetException)
				{
					// This shouldn't happen
				}
			}
		}
	}
}