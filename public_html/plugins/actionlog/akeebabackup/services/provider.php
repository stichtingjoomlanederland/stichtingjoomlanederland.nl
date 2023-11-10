<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Akeeba\Plugin\ActionLog\AkeebaBackup\Extension\AkeebaBackup;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

// Make sure that Joomla has registered the namespace for the plugin
if (!class_exists('\Akeeba\Plugin\ActionLog\AkeebaBackup\Extension\AkeebaBackup'))
{
	JLoader::registerNamespace('\Akeeba\Plugin\ActionLog\AkeebaBackup', realpath(__DIR__ . '/../src'));
}

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$subject = $container->get(DispatcherInterface::class);
				$config  = (array) PluginHelper::getPlugin('actionlog', 'akeebabackup');

				$plugin = new AkeebaBackup($subject, $config);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
