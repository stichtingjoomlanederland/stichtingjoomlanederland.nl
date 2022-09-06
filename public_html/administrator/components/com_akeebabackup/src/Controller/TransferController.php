<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Controller\Mixin\ControllerEvents;
use Akeeba\Component\AkeebaBackup\Administrator\Controller\Mixin\CustomACL;
use Akeeba\Component\AkeebaBackup\Administrator\Controller\Mixin\RegisterControllerTasks;
use Akeeba\Component\AkeebaBackup\Administrator\Controller\Mixin\ReusableModels;
use Akeeba\Component\AkeebaBackup\Administrator\Model\Exceptions\TransferIgnorableError;
use Akeeba\Component\AkeebaBackup\Administrator\Model\TransferModel;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;

class TransferController extends \Joomla\CMS\MVC\Controller\BaseController
{
	use ControllerEvents;
	use CustomACL;
	use ReusableModels;
	use RegisterControllerTasks;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	public function main()
	{
		$force = $this->input->getInt('force', 0);

		$view        = $this->getView();
		$view->force = $force;

		$this->display(false);
	}

	/**
	 * Reset the wizard
	 *
	 * @return  void
	 */
	public function reset()
	{
		$session = $this->app->getSession();

		$session->set('akeebabackup.transfer', null);
		$session->set('akeebabackup.transfer.url', null);
		$session->set('akeebabackup.transfer.url_status', null);
		$session->set('akeebabackup.transfer.ftpsupport', null);

		/** @var TransferModel $model */
		$model = $this->getModel();
		$model->resetUpload();

		$this->setRedirect(Route::_('index.php?option=com_akeebabackup&view=Transfer', false));
	}

	/**
	 * Cleans and checks the validity of the new site's URL
	 *
	 * @return  void
	 */
	public function checkUrl()
	{
		$session = $this->app->getSession();

		$url = $this->input->get('url', '', 'raw');

		/** @var TransferModel $model */
		$model  = $this->getModel();
		$result = $model->checkAndCleanUrl($url);

		$session->set('akeebabackup.transfer.url', $result['url']);
		$session->set('akeebabackup.transfer.url_status', $result['status']);

		@ob_end_clean();
		echo '###' . json_encode($result) . '###';

		$this->app->close();
	}

	/**
	 * Applies the FTP/SFTP connection information and makes some preliminary validation
	 *
	 * @return  void
	 */
	public function applyConnection()
	{
		$session = $this->app->getSession();

		$result = (object) [
			'status'    => true,
			'message'   => '',
			'ignorable' => false,
		];

		// Get the parameters from the request
		$transferOption = $this->input->getCmd('method', 'ftp');
		$force          = $this->input->getInt('force', 0);
		$ftpHost        = $this->input->get('host', '', 'raw');
		$ftpPort        = $this->input->getInt('port', null);
		$ftpUsername    = $this->input->get('username', '', 'raw');
		$ftpPassword    = $this->input->get('password', '', 'raw');
		$ftpPubKey      = $this->input->get('public', '', 'raw');
		$ftpPrivateKey  = $this->input->get('private', '', 'raw');
		$ftpPassive     = $this->input->getInt('passive', 1);
		$ftpPassiveFix  = $this->input->getInt('passive_fix', 1);
		$ftpDirectory   = $this->input->get('directory', '', 'raw');
		$chunkMode      = $this->input->getCmd('chunkMode', 'chunked');
		$chunkSize      = $this->input->getInt('chunkSize', '5242880');

		// Fix the port if it's missing
		if (empty($ftpPort))
		{
			switch ($transferOption)
			{
				case 'ftp':
				case 'ftpcurl':
					$ftpPort = 21;
					break;

				case 'ftps':
				case 'ftpscurl':
					$ftpPort = 990;
					break;

				case 'sftp':
				case 'sftpcurl':
					$ftpPort = 22;
					break;
			}
		}

		// Store everything in the session
		$session->set('akeebabackup.transfer.transferOption', $transferOption);
		$session->set('akeebabackup.transfer.force', $force);
		$session->set('akeebabackup.transfer.ftpHost', $ftpHost);
		$session->set('akeebabackup.transfer.ftpPort', $ftpPort);
		$session->set('akeebabackup.transfer.ftpUsername', $ftpUsername);
		$session->set('akeebabackup.transfer.ftpPassword', $ftpPassword);
		$session->set('akeebabackup.transfer.ftpPubKey', $ftpPubKey);
		$session->set('akeebabackup.transfer.ftpPrivateKey', $ftpPrivateKey);
		$session->set('akeebabackup.transfer.ftpDirectory', $ftpDirectory);
		$session->set('akeebabackup.transfer.ftpPassive', $ftpPassive ? 1 : 0);
		$session->set('akeebabackup.transfer.ftpPassiveFix', $ftpPassiveFix ? 1 : 0);
		$session->set('akeebabackup.transfer.chunkMode', $chunkMode);
		$session->set('akeebabackup.transfer.chunkSize', $chunkSize);

		/** @var TransferModel $model */
		$model = $this->getModel();

		try
		{
			$config = $model->getFtpConfig();
			$model->testConnection($config);
		}
		catch (TransferIgnorableError $e)
		{
			$result = (object) [
				'status'    => false,
				'ignorable' => true,
				'message'   => $e->getMessage(),
			];
		}
		catch (Exception $e)
		{
			$result = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'ignorable' => false,
			];
		}

		@ob_end_clean();

		echo '###' . json_encode($result) . '###';

		$this->app->close();
	}

	/**
	 * Initialise the upload: sends Kickstart and our add-on script to the remote server
	 *
	 * @return  void
	 */
	public function initialiseUpload()
	{
		$result = (object) [
			'status'    => true,
			'message'   => '',
			'ignorable' => false,
		];

		/** @var TransferModel $model */
		$model = $this->getModel();

		try
		{
			$config = $model->getFtpConfig();
			$model->initialiseUpload($config);
		}
		catch (TransferIgnorableError $e)
		{
			$result = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'ignorable' => true,
			];
		}
		catch (Exception $e)
		{
			$result = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'ignorable' => false,
			];
		}

		@ob_end_clean();

		echo '###' . json_encode($result) . '###';

		$this->app->close();
	}

	/**
	 * Perform an upload step. Pass start=1 to reset the upload and start over.
	 *
	 * @return  void
	 */
	public function upload()
	{
		/** @var TransferModel $model */
		$model = $this->getModel();

		if ($this->input->getBool('start', false))
		{
			$model->resetUpload();
		}

		try
		{
			$config       = $model->getFtpConfig();
			$uploadResult = $model->uploadChunk($config);
		}
		catch (Exception $e)
		{
			$uploadResult = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'totalSize' => 0,
				'doneSize'  => 0,
				'done'      => false,
			];
		}

		$result = (object) $uploadResult;

		@ob_end_clean();

		echo '###' . json_encode($result) . '###';

		$this->app->close();
	}
}