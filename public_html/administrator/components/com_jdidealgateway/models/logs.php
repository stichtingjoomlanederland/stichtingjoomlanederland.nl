<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2022 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Log Model.
 *
 * @package  JDiDEAL
 * @since    3.0.0
 */
class JdidealgatewayModelLogs extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 *
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'origin',
				'logs.origin',
				'order_id',
				'logs.order_id',
				'order_number',
				'logs.order_number',
				'currency',
				'logs.currency',
				'amount',
				'logs.amount',
				'card',
				'logs.card',
				'trans',
				'logs.trans',
				'psp',
				'logs.psp',
				'result',
				'logs.result',
			];
		}

		parent::__construct($config);
	}

	/**
	 * Clean any expired transaction logs.
	 *
	 * @return  void
	 *
	 * @since   6.6.0
	 */
	public function cleanLogs(): void
	{
		$params = ComponentHelper::getParams('com_jdidealgateway');

		if ((int) $params->get('expireDays', 0) === 0)
		{
			return;
		}

		$date  = new Date(strtotime('now -' . $params->get('expireDays') . ' days'));
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__jdidealgateway_logs'))
			->where($db->quoteName('date_added') . ' < ' . $db->quote($date->toSql()));
		$db->setQuery($query)
			->execute();
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function populateState($ordering = null, $direction = null): void
	{
		parent::populateState('logs.date_added', 'DESC');
	}

	/**
	 * Build an SQL query to load the list datlogs.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   4.0.0
	 */
	protected function getListQuery()
	{
		// Build the query
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					[
						'logs.id',
						'logs.trans',
						'logs.order_id',
						'logs.order_number',
						'logs.currency',
						'logs.amount',
						'logs.card',
						'logs.origin',
						'logs.date_added',
						'logs.result',
						'logs.paymentId',
						'logs.paymentReference',
						'profiles.alias',
						'profiles.psp',
					]
				)
			)
			->from($db->quoteName('#__jdidealgateway_logs', 'logs'))
			->leftJoin(
				$db->quoteName('#__jdidealgateway_profiles', 'profiles')
				. ' ON ' . $db->quoteName('profiles.id') . ' = ' . $db->quoteName('logs.profile_id')
			);

		$search = $this->getState('filter.search');

		if ($search)
		{
			$search      = $db->quote('%' . $search . '%');
			$searchArray = [
				$db->quoteName('logs.order_id') . ' LIKE ' . $search,
				$db->quoteName('logs.order_number') . ' LIKE ' . $search,
				$db->quoteName('logs.amount') . ' LIKE ' . $search,
				$db->quoteName('logs.trans') . ' LIKE ' . $search,
			];

			$query->where('(' . implode(' OR ', $searchArray) . ')');
		}

		$origin = $this->getState('filter.origin');

		if ($origin)
		{
			$query->where($db->quoteName('logs.origin') . ' = ' . $db->quote($origin));
		}

		$card = $this->getState('filter.card');

		if ($card)
		{
			$query->where($db->quoteName('logs.card') . ' = ' . $db->quote($card));
		}

		$psp = $this->getState('filter.psp');

		if ($psp)
		{
			$query->where($db->quoteName('logs.profile_id') . ' = ' . (int) $psp);
		}

		$currency = $this->getState('filter.currency');

		if ($currency)
		{
			$query->where($db->quoteName('logs.currency') . ' = ' . $db->quote($currency));
		}

		$result = $this->getState('filter.result');

		if ($result)
		{
			$query->where($db->quoteName('logs.result') . ' = ' . $db->quote($result));
		}

		$orderCol  = $this->state->get('list.ordering', 'logs.date_added');
		$orderDirn = $this->state->get('list.direction', 'desc');

		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	/**
	 * Load the history of a log entry.
	 *
	 * @return  string  The log history.
	 *
	 * @since   3.0.0
	 * @throws  Exception
	 */
	public function getHistory()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('history'))
			->from($db->quoteName('#__jdidealgateway_logs'))
			->where($db->quoteName('id') . ' = ' . (int) Factory::getApplication()->input->getInt('log_id', 0));
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Check if the notify script can be reached.
	 *
	 * @return  void
	 *
	 * @since   4.4.0
	 * @throws  Exception
	 */
	public function checkSystemRequirements(): void
	{
		$this->checkAliasExists();
		$this->checkNotifyScript();
		$this->checkCurlAvailable();
	}

	/**
	 * Check if there is an alias.
	 *
	 * @return  void
	 *
	 * @since   6.2.0
	 * @throws  Exception
	 */
	private function checkAliasExists(): void
	{
		$db = $this->getDbo();

		// Check if we have an alias
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__jdidealgateway_profiles'));
		$db->setQuery($query, 0, 1);

		$id = $db->loadResult();

		if (!$id)
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_ROPAYMENTS_NO_PROFILE_FOUND'), 'warning'
			);
		}
	}

	/**
	 * Check if the notify.php is available, only when we have an alias.
	 *
	 * @return  void
	 *
	 * @since   6.2.0
	 * @throws  Exception
	 */
	private function checkNotifyScript(): void
	{
		$app = Factory::getApplication();

		try
		{
			$options    = new Registry;
			$http       = HttpFactory::getHttp($options, ['curl', 'stream']);
			$url        = Uri::root() . 'cli/notify.php';
			$response   = $http->get($url);
			$statusCode = JVERSION < 4 ? $response->code
				: $response->getStatusCode();

			if ($statusCode !== 200)
			{
				$reason = JVERSION < 4 ? $response->body
					: $response->getReasonPhrase();
				$app->enqueueMessage(
					Text::sprintf(
						'COM_ROPAYMENTS_NOTIFY_NOT_AVAILABLE', $url, $url,
						$statusCode, $reason
					),
					'error'
				);
			}
		}
		catch (Exception $exception)
		{
			$app->enqueueMessage($exception->getMessage(), 'error');
		}
	}

	/**
	 * Check if cURL is active.
	 *
	 * @return  void
	 *
	 * @since   6.2.0
	 * @throws  Exception
	 */
	private function checkCurlAvailable(): void
	{
		if (!function_exists('curl_init') || !is_callable('curl_init'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_ROPAYMENTS_CURL_NOT_AVAILABLE'), 'error'
			);
		}
	}
}
