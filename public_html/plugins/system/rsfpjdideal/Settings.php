<?php
/**
 * @package     JDiDEAL
 * @subpackage  RSForm!Pro
 *
 * @author      Roland Dalmulder <contact@rolandd.com>
 * @copyright   Copyright (C) 2009 - 2022 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Ropayments\Rsformpro;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use RuntimeException;

defined('_JEXEC') or die;

/**
 * Get the different price masks
 *
 * @package  JDiDEAL
 * @since    7.0.0
 */
class Settings
{
	/**
	 * Load the form settings.
	 *
	 * @param   int  $formId  The form ID to get the settings for.
	 *
	 * @return  Registry  The form settings.
	 *
	 * @since   4.0.0
	 *
	 * @throws  RuntimeException
	 */
	public function loadFormSettings(int $formId): Registry
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__rsform_jdideal'))
			->where($db->quoteName('form_id') . ' = ' . $formId);
		$db->setQuery($query);
		$params = new Registry($db->loadResult());

		$noSettings = [
			'allowEmpty',
			'userEmail',
			'adminEmail',
			'additionalEmails',
			'profileAlias',
			'confirmationEmail'
		];

		foreach ($noSettings as $setting)
		{
			$params->def($setting, 0);
		}

		$yesSettings = [
			'sendEmailOnFailedPayment',
			'showMessage'
		];

		foreach ($yesSettings as $setting)
		{
			$params->def($setting, 1);
		}

		return $params;
	}
}
