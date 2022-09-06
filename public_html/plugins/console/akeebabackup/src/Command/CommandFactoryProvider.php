<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Console\AkeebaBackup\Command;

defined('_JEXEC') || die;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class CommandFactoryProvider implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->set(
			CommandFactoryInterface::class,
			function (Container $container) {
				$factory = new CommandFactory();

				$factory->setMVCFactory($container->get(MVCFactoryInterface::class));

				return $factory;
			}
		);
	}
}