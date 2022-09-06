<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\UpdatesModel;

/**
 * Get the version information of Akeeba Backup
 */
class GetVersion extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array $parameters The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = array())
	{
		$edition = AKEEBABACKUP_PRO ? 'pro' : 'core';

		return (object)array(
			'api'        => 500,
			'component'  => AKEEBABACKUP_VERSION,
			'date'       => AKEEBABACKUP_DATE,
			'edition'    => $edition,
		);
	}
}
