<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Akeeba\Plugin\WebServices\AkeebaBackup\Extension\AkeebaBackup;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 * @since   9.6.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$pluginsParams = (array) PluginHelper::getPlugin('webservices', 'akeebabackup');
				$dispatcher    = $container->get(DispatcherInterface::class);
				$plugin        = new AkeebaBackup($dispatcher, $pluginsParams);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
