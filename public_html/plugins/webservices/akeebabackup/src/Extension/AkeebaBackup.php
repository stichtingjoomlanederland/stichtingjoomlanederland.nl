<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\WebServices\AkeebaBackup\Extension;

defined('_JEXEC') || die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

class AkeebaBackup extends CMSPlugin implements SubscriberInterface
{
	protected $allowLegacyListeners = false;

	/**
	 * @inheritDoc
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeApiRoute' => 'registerRoutes',
		];
	}

	/**
	 * Register the Joomla API application routes for Akeeba Backup
	 *
	 * @param   Event  $event
	 *
	 * @return  void
	 * @since   9.6.0
	 */
	public function registerRoutes(Event $event): void
	{
		/** @var ApiRouter $router */
		[$router] = array_values($event->getArguments());

		$defaults = [
			'component' => 'com_akeebabackup',
			// Allow public access (allows us to authenticate with the Akeeba Backup Secret Word)
			'public'    => true,
			'format'    => [
				'application/json',
			],
		];

		$routes = [
			// Legacy v2 Akeeba Remote JSON API handler (for future Remote CLI versions)
			new Route(
				['GET', 'POST'],
				'v2/akeebabackup',
				'api.simpleDisplay',
				[],
				array_merge($defaults, [
					'_apiVersion' => 2,
				])
			),
			// Legacy v2 Akeeba Remote JSON API handler (for Remote CLI versions up to 3.0.x)
			new Route(
				['GET', 'POST'],
				'v2/akeebabackup/index.php',
				'api.simpleDisplay',
				[],
				array_merge($defaults, [
					'_apiVersion' => 2,
				])
			),
			// v3 Akeeba Remote JSON API handler
			new Route(
				['GET', 'POST'],
				'v3/akeebabackup/:method',
				'api.simpleDisplay',
				[
					'method' => '[a-zA-Z0-9_\.-]+',
				],
				array_merge($defaults, [
					'_apiVersion' => 3,
				])
			),
		];

		// Add the routes to the router.
		$router->addRoutes($routes);

		$this->fixMissingAcceptHeader();
		$this->fixV2APIInput();
	}

	/**
	 * Returns the current API path (without index.php)
	 *
	 * @return  string
	 * @since   9.6.0
	 */
	private function getPath(): string
	{
		$path     = Uri::getInstance()->getPath();
		$basePath = Uri::base(true);

		if (str_starts_with($path, $basePath))
		{
			$path = substr($path, strlen($basePath));
		}

		$path = trim($path, '/');

		if (substr($path, 0, 10) === 'index.php/')
		{
			$path = substr($path, 10);
		}

		return $path;
	}

	/**
	 * Conditionally fix missing Accept header.
	 *
	 * Clients may not set an Accept header on their requests. However, the Joomla API application cannot accept a
	 * NULL value for the Accept header; it will throw an HTTP 406 error. As a result, I need to check if this is
	 * the case and set the Accept header manually.
	 *
	 * @return  void
	 * @since   9.6.0
	 */
	private function fixMissingAcceptHeader(): void
	{
		$path         = $this->getPath();
		$acceptHeader = $this->getApplication()->input->server->getString('HTTP_ACCEPT');

		if (
			(
				substr($path, 0, 15) === 'v3/akeebabackup'
				|| substr($path, 0, 15) === 'v2/akeebabackup'
			)
			&& $acceptHeader === null
		)
		{
			$this->getApplication()->input->server->set('HTTP_ACCEPT', 'application/json');
		}
	}

	private function fixV2APIInput(): void
	{
		$path  = $this->getPath();

		if (substr($path, 0, 15) !== 'v2/akeebabackup')
		{
			return;
		}

		/**
		 * Remove "bad" query string parameters.
		 *
		 * v2 API clients send the option, format, view, and possibly tmpl query string parameters. These can confuse
		 * the Joomla API Application's routing, so we need to remove them.
		 */
		$queryVars = Uri::getInstance()->getQuery(true);

		foreach (['option', 'format', 'view', 'tmpl'] as $key)
		{
			if (!isset($queryVars[$key]))
			{
				continue;
			}

			unset($queryVars[$key]);
		}

		Uri::getInstance()->setQuery($queryVars);
	}
}