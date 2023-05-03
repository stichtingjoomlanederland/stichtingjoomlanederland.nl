<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Engine\Platform;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

#[\AllowDynamicProperties]
class ScheduleModel extends BaseModel
{
	public function getPaths()
	{
		$ret = (object) [
			'cli'      => (object) [
				'supported' => false,
				'path'      => false,
			],
			'joomla'      => (object) [
				'supported' => false,
			],
			'altcli'   => (object) [
				'supported' => false,
				'path'      => false,
			],
			'frontend' => (object) [
				'supported' => false,
				'path'      => false,
			],
			'json'     => (object) [
				'supported' => false,
				'path'      => false,
			],
			'info'     => (object) [
				'windows'   => false,
				'php_path'  => false,
				'root_url'  => false,
				'secret'    => '',
				'jsonapi'   => false,
				'legacyapi' => false,
			],
		];

		$currentProfileID = Platform::getInstance()->get_active_profile();
		$siteRoot         = rtrim(realpath(JPATH_ROOT), DIRECTORY_SEPARATOR);

		$ret->info->windows   = (DIRECTORY_SEPARATOR == '\\') || (substr(strtoupper(PHP_OS), 0, 3) == 'WIN');
		$ret->info->php_path  = $ret->info->windows ? 'c:\path\to\php.exe' : '/path/to/php';
		$ret->info->root_url  = rtrim(Uri::root(false), '/');
		$ret->info->secret    = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		$ret->info->jsonapi   = Platform::getInstance()->get_platform_configuration_option('jsonapi_enabled', '');
		$ret->info->legacyapi = Platform::getInstance()->get_platform_configuration_option('legacyapi_enabled', '');

		// Get information for Joomla Scheduled Tasks
		$ret->joomla->supported = version_compare(JVERSION, '4.1.0', 'ge') &&
			PluginHelper::isEnabled('task', 'akeebabackup');

		// Get information for CLI CRON script
		$ret->cli->supported = true;
		$ret->cli->path      = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'joomla.php akeeba:backup:take']);

		if ($currentProfileID != 1)
		{
			$ret->cli->path .= ' --profile=' . $currentProfileID;
		}

		// Get information for alternative CLI CRON script
		$ret->altcli->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret))
		{
			$ret->altcli->path = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'joomla.php akeeba:backup:alternate']);

			if ($currentProfileID != 1)
			{
				$ret->altcli->path .= ' --profile=' . $currentProfileID;
			}
		}

		// Get information for front-end backup
		$ret->frontend->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret) && $ret->info->legacyapi)
		{
			$ret->frontend->path = 'index.php?option=com_akeebabackup&view=Backup&key='
				. urlencode($ret->info->secret);

			if ($currentProfileID != 1)
			{
				$ret->frontend->path .= '&profile=' . $currentProfileID;
			}
		}

		// Get information for JSON API backups
		$ret->json->supported = $ret->info->jsonapi;
		$ret->json->path      = 'index.php?option=com_akeebabackup&view=Api&format=raw';

		return $ret;
	}

	public function getCheckPaths()
	{
		$ret = (object) [
			'cli'      => (object) [
				'supported' => false,
				'path'      => false,
			],
			'altcli'   => (object) [
				'supported' => false,
				'path'      => false,
			],
			'frontend' => (object) [
				'supported' => false,
				'path'      => false,
			],
			'info'     => (object) [
				'windows'   => false,
				'php_path'  => false,
				'root_url'  => false,
				'secret'    => '',
				'jsonapi'   => false,
				'legacyapi' => false,
			],
		];

		$currentProfileID = Platform::getInstance()->get_active_profile();
		$siteRoot         = rtrim(realpath(JPATH_ROOT), DIRECTORY_SEPARATOR);

		$ret->info->windows   = (DIRECTORY_SEPARATOR == '\\') || (substr(strtoupper(PHP_OS), 0, 3) == 'WIN');
		$ret->info->php_path  = $ret->info->windows ? 'c:\path\to\php.exe' : '/path/to/php';
		$ret->info->root_url  = rtrim(Uri::root(false), '/');
		$ret->info->secret    = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		$ret->info->jsonapi   = Platform::getInstance()->get_platform_configuration_option('jsonapi_enabled', '');
		$ret->info->legacyapi = Platform::getInstance()->get_platform_configuration_option('legacyapi_enabled', '');

		// Get information for CLI CRON script
		$ret->cli->supported = true;
		$ret->cli->path      = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'joomla.php akeeba:backup:check']);

		if ($currentProfileID != 1)
		{
			$ret->cli->path .= ' --profile=' . $currentProfileID;
		}

		// Get information for alternative CLI CRON script
		$ret->altcli->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret))
		{
			$ret->altcli->path = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'joomla.php akeeba:backup:alternate_check']);

			if ($currentProfileID != 1)
			{
				$ret->altcli->path .= ' --profile=' . $currentProfileID;
			}
		}

		// Get information for front-end backup
		$ret->frontend->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret) && $ret->info->legacyapi)
		{
			$ret->frontend->path = 'index.php?option=com_akeebabackup&view=Check&key='
				. urlencode($ret->info->secret);

			if ($currentProfileID != 1)
			{
				$ret->frontend->path .= '&profile=' . $currentProfileID;
			}
		}

		return $ret;
	}
}