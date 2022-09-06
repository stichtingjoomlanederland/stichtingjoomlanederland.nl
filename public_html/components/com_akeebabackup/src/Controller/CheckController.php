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
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Akeeba\Component\AkeebaBackup\Site\Controller\Mixin\FrontEndPermissions;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

class CheckController extends BaseController
{
	use ControllerEvents;
	use RegisterControllerTasks;
	use ReusableModels;
	use FrontEndPermissions;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	/**
	 * Checks for failed backups and sends out any notification emails
	 */
	public function main()
	{
		// Check permissions
		$this->checkPermissions();

		/** @var StatisticsModel $model */
		$model  = $this->getModel('Statistics', 'Administrator');
		$result = $model->notifyFailed();

		$message = $result['result'] ? '200 ' : '500 ';
		$message .= implode(', ', $result['message']);

		@ob_end_clean();
		header('Content-type: text/plain');
		header('Connection: close');
		echo $message;
		flush();

		$this->app->close();
	}

}