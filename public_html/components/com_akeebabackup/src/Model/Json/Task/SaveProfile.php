<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Exception;
use RuntimeException;

/**
 * Saves a backup profile
 */
class SaveProfile extends AbstractTask
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
		// Get the passed configuration values
		$defConfig = [
			'profile'     => 0,
			'description' => null,
			'quickicon'   => null,
			'source'      => 0,
		];

		$defConfig = array_merge($defConfig, $parameters);

		$profile     = (int) $defConfig['profile'];
		$description = $defConfig['description'];
		$quickicon   = $defConfig['quickicon'];
		$source      = (int) $defConfig['source'];

		if ($profile <= 0)
		{
			$profile = null;
		}

		// At least one of these parameters is required
		if (empty($profile) && empty($source) && empty($description))
		{
			throw new RuntimeException('Invalid profile ID', 404);
		}

		// Get a profile model
		/** @var ProfileModel $profileModel */
		$profileModel = $this->factory->createModel('Profile', 'Administrator', ['ignore_request' => true]);
		$profileTable = $profileModel->getTable();

		// Load the profile
		$sourceId = empty($profile) ? $source : $profile;

		if (!empty($sourceId))
		{
			$profileTable->load($sourceId);
		}

		$updates = [
			'id' => $profile,
		];

		$profileModel->id = $profile;

		if ($description)
		{
			$updates['description'] = $description;
		}

		if (!is_null($quickicon))
		{
			$updates['quickicon'] = (int) $quickicon;

		}

		$profileModel->save($updates);

		return true;
	}
}
