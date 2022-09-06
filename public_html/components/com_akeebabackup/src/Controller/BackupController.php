<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Controller\Mixin\ControllerEvents;
use Akeeba\Component\AkeebaBackup\Administrator\Controller\Mixin\RegisterControllerTasks;
use Akeeba\Component\AkeebaBackup\Administrator\Controller\Mixin\ReusableModels;
use Akeeba\Component\AkeebaBackup\Administrator\Model\BackupModel;
use Akeeba\Component\AkeebaBackup\Site\Controller\Mixin\ActivateProfile;
use Akeeba\Component\AkeebaBackup\Site\Controller\Mixin\CustomRedirection;
use Akeeba\Component\AkeebaBackup\Site\Controller\Mixin\FrontEndPermissions;
use Akeeba\Engine\Factory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;

class BackupController extends BaseController
{
	use ControllerEvents;
	use RegisterControllerTasks;
	use ReusableModels;
	use FrontEndPermissions;
	use ActivateProfile;
	use CustomRedirection;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');

		if (!defined('AKEEBA_BACKUP_ORIGIN'))
		{
			define('AKEEBA_BACKUP_ORIGIN', 'frontend');
		}
	}

	/**
	 * Start a front-end legacy backup
	 *
	 * @return  void
	 */
	public function main()
	{
		$this->checkPermissions();
		$this->setProfile();

		// Get the backup ID
		$backupId = $this->input->get('backupid', null, 'cmd');

		if (empty($backupId))
		{
			$backupId = null;
		}

		/** @var BackupModel $model */
		$model = $this->getModel('Backup', 'Administrator');

		$model->setState('tag', AKEEBA_BACKUP_ORIGIN);
		$model->setState('backupid', $backupId);
		$model->setState('description', $model->getDefaultDescription() . ' (Frontend)');
		$model->setState('comment', '');

		$array    = $model->startBackup();
		$backupId = $model->getState('backupid', null);

		$this->processEngineReturnArray($array, $backupId);
	}

	/**
	 * Step through a front-end legacy backup
	 *
	 * @return  void
	 */
	public function step()
	{
		// Setup
		$this->checkPermissions();
		$this->setProfile();

		// Get the backup ID
		$backupId = $this->input->get('backupid', null, 'cmd');

		if (empty($backupId))
		{
			$backupId = null;
		}

		/** @var BackupModel $model */
		$model = $this->getModel('Backup', 'Administrator');

		$model->setState('tag', AKEEBA_BACKUP_ORIGIN);
		$model->setState('backupid', $backupId);

		$array = $model->stepBackup();

		$backupId = $model->getState('backupid', null);

		$this->processEngineReturnArray($array, $backupId);
	}

	/**
	 * Used by the tasks to process Akeeba Engine's return array. Depending on the result and the component options we
	 * may throw text output or send an HTTP redirection header.
	 *
	 * @param   array        $array     The return array to process
	 * @param   string|null  $backupId  The backup ID (used to step the backup process)
	 */
	private function processEngineReturnArray(array $array, ?string $backupId)
	{
		if ($array['Error'] != '')
		{
			@ob_end_clean();
			echo '500 ERROR -- ' . $array['Error'];
			flush();

			$this->app->close();
		}

		if ($array['HasRun'] == 1)
		{
			// All done
			Factory::nuke();
			Factory::getFactoryStorage()->reset();
			@ob_end_clean();
			header('Content-type: text/plain');
			header('Connection: close');
			echo '200 OK';
			flush();

			$this->app->close();
		}

		$noRedirect = $this->input->get('noredirect', 0, 'int');

		if ($noRedirect != 0)
		{
			@ob_end_clean();
			header('Content-type: text/plain');
			header('Connection: close');
			echo "301 More work required -- BACKUPID ###$backupId###";
			flush();

			$this->app->close();
		}

		$uri  = Uri::getInstance();

		$uri->delVar('key');
		$uri->setVar('option', 'com_akeebabackup');
		$uri->setVar('view', 'Backup');
		$uri->setVar('task', 'step');
		$uri->setVar('profile', $this->input->get('profile', 1, 'int'));

		if (!empty($backupId))
		{
			$uri->setVar('backupid', $backupId);
		}

		// Maybe we have a multilingual site?
		$language    = $this->app->getLanguage();
		$languageTag = $language->getTag();

		$uri->setVar('lang', $languageTag);

		$key            = $this->input->get('key', '', 'raw');
		$redirectionUrl = $uri->toString() . '&key=' . urlencode($key);

		$this->customRedirect($redirectionUrl);
	}

}