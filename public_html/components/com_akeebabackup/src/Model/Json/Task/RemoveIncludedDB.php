<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\MultipledatabasesModel;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use RuntimeException;

/**
 * Remove an extra database definition
 */
class RemoveIncludedDB extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException|Exception  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		$filter = InputFilter::getInstance();

		// Get the passed configuration values
		$defConfig = [
			'profile' => 0,
			'name'    => '',
		];

		$defConfig = array_merge($defConfig, $parameters);

		$profile = $filter->clean($defConfig['profile'], 'int');
		$name    = $filter->clean($defConfig['name'], 'string');

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		// We need a uuid
		if (empty($name))
		{
			throw new RuntimeException('The database name is required', 500);
		}

		// Set the active profile
		Factory::getApplication()->getSession()->set('akeebabackup.profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var MultipledatabasesModel $model */
		$model = $this->factory->createModel('Multipledatabases', 'Administrator', ['ignore_request' => true]);

		return $model->remove($name);
	}
}
