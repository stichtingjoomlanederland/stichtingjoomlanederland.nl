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
use Exception;
use Joomla\CMS\Filter\InputFilter;
use RuntimeException;

/**
 * Test an extra database definition
 */
class TestDBConnection extends AbstractTask
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
			'connection' => [],
		];

		$defConfig = array_merge($defConfig, $parameters);

		$connection = $filter->clean($defConfig['connection'], 'array');

		if (
			empty($connection) || !isset($connection['host']) || !isset($connection['driver'])
			|| !isset($connection['database']) || !isset($connection['user'])
			|| !isset($connection['password'])
		)
		{
			throw new RuntimeException('Connection information missing or incomplete', 500);
		}

		/** @var MultipledatabasesModel $model */
		$model = $this->factory->createModel('Multipledatabases', 'Administrator', ['ignore_request' => true]);

		return $model->test($connection);
	}
}
