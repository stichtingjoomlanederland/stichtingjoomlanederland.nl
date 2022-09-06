<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\BrowserModel;
use Joomla\CMS\Filter\InputFilter;

/**
 * Return folder browser results
 */
class Browse extends AbstractTask
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
			'folder'        => '',
			'processfolder' => 0,
		];

		$defConfig = array_merge($defConfig, $parameters);

		$folder        = $filter->clean($defConfig['folder'], 'string');
		$processFolder = $filter->clean($defConfig['processfolder'], 'bool');

		/** @var BrowserModel $model */
		$model = $this->factory->createModel('Browser', 'Administrator');
		$model->setState('folder', $folder);
		$model->setState('processfolder', $processFolder);
		$model->makeListing();

		$ret = [
			'folder'                => $model->getState('folder'),
			'folder_raw'            => $model->getState('folder_raw'),
			'parent'                => $model->getState('parent'),
			'exists'                => $model->getState('exists'),
			'inRoot'                => $model->getState('inRoot'),
			'openbasedirRestricted' => $model->getState('openbasedirRestricted'),
			'writable'              => $model->getState('writable'),
			'subfolders'            => $model->getState('subfolders'),
			'breadcrumbs'           => $model->getState('breadcrumbs'),
		];

		return $ret;
	}
}
