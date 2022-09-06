<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

class RsformViewDeletesubmission extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->params = JFactory::getApplication()->getParams('com_rsform');
		$this->form   = $this->get('Form');

		$this->document->setMetaData('robots', 'noindex');

		parent::display($tpl);
	}
}