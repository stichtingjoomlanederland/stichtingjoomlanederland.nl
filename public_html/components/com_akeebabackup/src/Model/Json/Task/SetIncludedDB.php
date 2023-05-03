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
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;

/**
 * Set up or edit an extra database definition
 */
class SetIncludedDB extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		$filter = InputFilter::getInstance();

		// Get the passed configuration values
		$defConfig = [
			'profile'    => 0,
			'name'       => '',
			'connection' => [],
			'test'       => true,
		];

		$defConfig = array_merge($defConfig, $parameters);

		$profile    = $filter->clean($defConfig['profile'], 'int');
		$name       = $filter->clean($defConfig['name'], 'string');
		$connection = $filter->clean($defConfig['connection'], 'array');
		$test       = $filter->clean($defConfig['test'], 'bool');

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		if (
			empty($connection) || !isset($connection['host']) || !isset($connection['driver'])
			|| !isset($connection['database']) || !isset($connection['user'])
			|| !isset($connection['password'])
		)
		{
			throw new \RuntimeException('Connection information missing or incomplete', 500);
		}

		// Set the active profile
		Factory::getApplication()->getSession()->set('akeebabackup.profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var MultipledatabasesModel $model */
		$model = $this->factory->createModel('Multipledatabases', 'Administrator', ['ignore_request' => true]);

		if ($test)
		{
			$result = $model->test($connection);

			if (!$result['status'])
			{
				throw new \RuntimeException('Connection test failed: ' . $result['message'], 500);
			}
		}

		return $model->setFilter($name, $connection);
	}
}
