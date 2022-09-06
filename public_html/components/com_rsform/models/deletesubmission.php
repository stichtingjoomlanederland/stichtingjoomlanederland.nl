<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

class RsformModelDeletesubmission extends JModelForm
{
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsform.deletesubmission', 'deletesubmission', array('control' => false, 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		$hash = JFactory::getApplication()->input->getCmd('hash', '');
		if (strlen($hash) === 32)
		{
			$form->setValue('hash', null, $hash);
		}

		return $form;
	}
}