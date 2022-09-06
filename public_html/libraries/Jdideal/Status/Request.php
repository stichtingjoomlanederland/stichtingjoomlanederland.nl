<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2022 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Jdideal\Status;

use Exception;
use InvalidArgumentException;
use Jdideal\Gateway;
use Jdideal\Psp\PspInterface;
use JEventDispatcher;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use RuntimeException;
use TableLog;

defined('_JEXEC') or die;

/**
 * Handle the status requests.
 *
 * Available actions are below. Any other action will take the Open Status
 * - SUCCESS
 * - CANCELLED
 * - FAILURE
 * - EXPIRED
 * - REFUNDED
 * - CHARGEBACK
 * - TRANSFER
 *
 * @package  JDiDEAL
 * @since    2.0.0
 */
class Request
{
	/**
	 * CMS Application
	 *
	 * @var    SiteApplication
	 * @since  5.0.0
	 */
	private $app;

	/**
	 * The Joomla Mailer class
	 *
	 * @var    Mail
	 * @since  4.0.0
	 */
	private $mail;

	/**
	 * The email address of the site sending the email
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $from;

	/**
	 * The name of the site sending the email
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $fromName;

	/**
	 * The URL of the site to create URLs in the emails
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $siteUrl;

	/**
	 * Constructor.
	 *
	 * @since   5.0.0
	 * @throws  Exception
	 */
	public function __construct()
	{
		Table::addIncludePath(
			JPATH_ADMINISTRATOR . '/components/com_jdidealgateway/tables'
		);

		$this->app = Factory::getApplication();
	}

	/**
	 * Process the ping.
	 *
	 * @return  array
	 *
	 * @since   4.14.2
	 * @throws  Exception
	 */
	public function batch(): array
	{
		$jdideal = new Gateway;
		$input   = Factory::getApplication()->input;

		/** @var PspInterface $notifier */
		$notifier = $jdideal->loadNotifier($jdideal, $input);

		if (method_exists($notifier, 'processRequest'))
		{
			if (!$notifier->processRequest())
			{
				return [];
			}
		}

		$isCustomer = $notifier->isCustomer();

		// If we are the customer or if we are a payment provider that does not need to call home, process directly
		if ($isCustomer || !$notifier->callHome())
		{
			$jdideal->log('Process directly', $notifier->getLogId());

			return $this->process($notifier);
		}

		$notifier->getOrders($jdideal);

		$result = [
			'isCustomer' => false,
			'status'     => '',
		];

		// Process the transactions
		while ($notifier->processTransaction())
		{
			$jdideal->log('Process transaction', $notifier->getLogId());
			$result = $this->process($notifier);
		}

		return $result;
	}

	/**
	 * Process the status request.
	 *
	 * @param   mixed  $notifier  The notifier class
	 *
	 * @return  array  The transaction data.
	 *
	 * @since   3.0.0
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 * @throws  Exception
	 */
	private function process($notifier): array
	{
		$jdideal    = new Gateway;
		$status     = false;
		$errorLevel = '';
		$redirect   = false;
		$recurring  = false;
		$processed  = false;

		try
		{
			$logId              = $notifier->getLogId();
			$transactionDetails = $jdideal->getDetails($logId);
			$jdideal->loadConfiguration($transactionDetails->alias);

			if ($jdideal->get('recurring', false)
				&& method_exists(
					$notifier, 'getPaymentId'
				))
			{
				$paymentId = $notifier->getPaymentId();

				if ($paymentId
					&& ($this->doesTransactionExist($paymentId) === false))
				{
					$logId     = $this->createTransaction($logId, $paymentId);
					$recurring = true;

					$this->triggerPlugin(
						'onCreateRenewal',
						[
							'paymentId'     => $paymentId,
							'transactionId' => $notifier->getTransactionId(),
						]
					);
				}
			}

			$transactionDetails = $jdideal->getDetails($logId);
			$jdideal->loadConfiguration($transactionDetails->alias);
		}
		catch (Exception $exception)
		{
			$this->writeErrorLog($exception->getMessage());

			throw new RuntimeException(
				$exception->getMessage(), $exception->getCode()
			);
		}

		if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
		{
			$jdideal->log('User agent: ' . $_SERVER['HTTP_USER_AGENT'], $logId);
		}

		$jdideal->log('Query string: ' . $_SERVER['QUERY_STRING'], $logId);

		$user       = Factory::getUser();
		$isCustomer = $notifier->isCustomer();

		if ($isCustomer)
		{
			$jdideal->log(
				'User ' . $user->get('username', 'Guest') . ' is calling.',
				$logId
			);
		}
		else
		{
			$jdideal->log('Payment provider calling', $logId);
		}

		if (!is_object($transactionDetails))
		{
			$jdideal->log('No transaction details have been found.', $logId);

			throw new RuntimeException(
				Text::sprintf('COM_ROPAYMENTS_NO_LOGDETAILS_FOUND', $logId)
			);
		}

		// Make the customer wait for the payment result
		if ($isCustomer && !$transactionDetails->processed
			&& !$notifier->canUseCustomerStatus())
		{
			$jdideal->log('Going to sleep', $logId);
			sleep(3);
			$jdideal->log('Waking up', $logId);

			// Get the new transaction details
			$transactionDetails = $jdideal->getDetails($logId, 'id', true);

			// Add some logging
			$jdideal->log('Result: ' . $transactionDetails->result, $logId);
			$jdideal->log(
				'Is transaction processed: ' . ($transactionDetails->processed
					? 'Yes' : 'No'), $logId
			);

			// If we have no transaction status and cannot use the customer status this is the end of the line
			if ($transactionDetails->result !== 'TRANSFER'
				&& !$transactionDetails->processed)
			{
				$jdideal->status('UNKNOWN', $logId);

				$url = 'index.php?option=com_jdidealgateway&view=status&lang='
					. $transactionDetails->language;

				$jdideal->log(
					'Raw URL: ' . $url, $logId
				);

				$redirect = Route::_($url, false);

				$jdideal->log(
					'Routed URL: ' . $url, $logId
				);

				// Workaround for Language filter plugin stripping the sef language when the option Remove URL language code is enabled
				if (PluginHelper::isEnabled('system', 'languagefilter'))
				{
					$languageFilter = PluginHelper::getPlugin(
						'system', 'languagefilter'
					);
					$languageParams = new Registry($languageFilter->params);

					if ($this->app->get('sef', 0)
						&& $languageParams->get('remove_default_prefix', 0))
					{
						$languageCodes = LanguageHelper::getLanguages(
							'lang_code'
						);

						if (array_key_exists(
							$transactionDetails->language, $languageCodes
						))
						{
							$redirect = '/'
								. $languageCodes[$transactionDetails->language]->sef
								. $redirect;
						}
					}
				}

				// Clear the URL from the /cli prefix
				$redirect = str_ireplace('/cli', '', $redirect);

				// Check if there is an Itemid, so yes, no menu item is found and we should not add anything extra
				if (stristr($redirect, 'Itemid='))
				{
					$redirect = str_ireplace('/component/jdidealgateway', '', $redirect);
				}

				{
					$jdideal->log(
						'Redirect to unknown status page: ' . $redirect, $logId
					);
				}
			}
		}

		// Check if the payment has already been checked and only if the payment provider is calling
		if (!$redirect && (!$transactionDetails->processed || !$isCustomer))
		{
			$config         = Factory::getConfig();
			$this->mail     = Factory::getMailer();
			$this->from     = $config->get('mailfrom');
			$this->fromName = $config->get('fromname');
			$this->siteUrl  = $jdideal->getUrl();

			$resultData            = [];
			$resultData['isValid'] = true;
			$resultData['message'] = '';

			$jdideal->log('Get transaction ID', $logId);
			$transactionID = $notifier->getTransactionId();
			$jdideal->log('Received transaction ID ' . $transactionID, $logId);

			$jdideal->log('Get transaction status', $logId);
			$transactionStatus = $notifier->transactionStatus($jdideal, $logId);
			$status            = $transactionStatus['isOK'];

			try
			{
				$extensionAddon = $jdideal->getAddon(
					$transactionDetails->origin
				);
			}
			catch (Exception $exception)
			{
				$jdideal->log($exception->getMessage(), $logId);

				// Inform the admin of the missing file
				$this->mail->clearAddresses();
				$body = Text::sprintf(
					'COM_ROPAYMENTS_ADDON_FILE_MISSING_DESC',
					$transactionDetails->origin
				);
				$this->mail->sendMail(
					$this->from,
					$this->fromName,
					$this->from,
					Text::sprintf(
						'COM_ROPAYMENTS_ADDON_FILE_MISSING',
						$transactionDetails->origin
					),
					$body,
					false
				);

				throw new RuntimeException($exception->getMessage());
			}

			$orderData                 = [];
			$orderData['order_number'] = $transactionDetails->order_number;
			$orderData['order_id']     = $transactionDetails->order_id;

			try
			{
				$orderData = $extensionAddon->getOrderInformation(
					$transactionDetails->order_id, $orderData
				);
			}
			catch (Exception $exception)
			{
				$jdideal->log(
					'Caught an error: ' . $exception->getMessage(), $logId
				);
			}

			if (!$transactionStatus['isOK'])
			{
				$jdideal->log('Transaction status is not OK', $logId);

				// Set the result is false
				$resultData['isValid'] = false;
				$errorLevel            = 'error';

				// Status Request failed
				$message = Text::_(
					'COM_ROPAYMENTS_STATUS_COULD_NOT_BE_RETRIEVED'
				);

				// Log the error message
				if (array_key_exists('error_message', $transactionStatus))
				{
					$jdideal->log($transactionStatus['error_message'], $logId);
					$message .= Text::sprintf(
						'COM_ROPAYMENTS_IDEAL_ERROR_MESSAGE',
						$transactionStatus['error_message']
					);
				}

				$resultData['message'] = $message;

				// Store the result
				$jdideal->status('OPEN', $logId);

				$this->emailResultNOK(
					$jdideal, $transactionDetails, $orderData
				);
			}
			else
			{
				$responseStatus = $transactionStatus['suggestedAction'];

				if (!$responseStatus)
				{
					$jdideal->log(
						'No response status has been received for transaction ID '
						. $transactionID, $logId
					);

					throw new InvalidArgumentException(
						Text::_('COM_ROPAYMENTS_NO_RESPONSESTATUS')
					);
				}

				$jdideal->log(
					'Payment has ' . $responseStatus . ' status', $logId
				);

				if ($responseStatus === 'CHARGEBACK' || $responseStatus === 'REFUNDED')
				{
					$jdideal->log(
						'Creating a new transaction entry for the ' . strtolower($responseStatus) . ' status.', $logId
					);
					$newPaymentId = $notifier->getPaymentId();
					$logId        = $this->createTransaction($logId, $newPaymentId);

					$this->triggerPlugin(
						'onCreate' . ucfirst(strtolower($responseStatus)),
						[
							'paymentId'     => $newPaymentId,
							'transactionId' => $transactionID,
							'logId'         => $logId,
						]
					);
				}

				$jdideal->log(
					'Current order status ' . $orderData['order_status'], $logId
				);

				$jdideal->status($responseStatus, $logId);

				if (array_key_exists('error_message', $transactionStatus))
				{
					$jdideal->log($transactionStatus['error_message'], $logId);
				}

				if (!$recurring
					&& !$jdideal->canUpdateOrder(
						$orderData['order_status']
					))
				{
					// Status doesn't match, inform the administrator if needed
					if ($jdideal->get('status_mismatch'))
					{
						$options                    = [];
						$options['type']            = 'status_mismatch';
						$options['expected_status'] = $jdideal->get(
							'pendingStatus'
						);
						$options['found_status']
						                            = $orderData['order_status'];

						if (array_key_exists('order_number', $orderData))
						{
							$options['order_number']
								= $orderData['order_number'];
						}

						$options['status'] = $responseStatus;
						$jdideal->informAdmin($options);
					}

					$jdideal->log(
						'Order has status ' . $orderData['order_status']
						. ' but according to the settings it needs status '
						. $jdideal->get('pendingStatus')
						. ' to be able to update the order',
						$logId
					);

					throw new RuntimeException(
						Text::sprintf(
							'COM_ROPAYMENTS_ORDER_STATUS_DOESNT_MATCH_PENDING',
							$orderData['order_status'],
							$jdideal->get('pendingStatus')
						)
					);
				}

				$orderData['order_comment'] = Text::_(
						'COM_ROPAYMENTS_TRANSACTION_ID'
					) . ' ' . $transactionID;

				$defaultStatus = $jdideal->get('openStatus');

				switch (strtoupper($responseStatus))
				{
					case 'SUCCESS':
						// Check if the current order status matches the order status the order must have to be able to update it
						$orderData['order_status'] = $jdideal->get(
							'verifiedStatus', $defaultStatus
						);
						$processed                 = true;
						break;
					case 'CANCELLED':
						$orderData['order_status'] = $jdideal->get(
							'cancelledStatus', $defaultStatus
						);
						$processed                 = true;
						break;
					case 'FAILURE':
						$orderData['order_status'] = $jdideal->get(
							'failedStatus', $defaultStatus
						);
						$processed                 = true;
						break;
					case 'EXPIRED':
						$orderData['order_status'] = $jdideal->get(
							'expiredStatus', $defaultStatus
						);
						$processed                 = true;
						break;
					case 'REFUNDED':
						$orderData['order_status'] = $jdideal->get(
							'refundStatus', $defaultStatus
						);
						$processed                 = true;
						break;
					case 'CHARGEBACK':
						$orderData['order_status'] = $jdideal->get(
							'chargebackStatus', $defaultStatus
						);
						$processed                 = true;
						break;
					case 'TRANSFER':
						$orderData['order_status'] = $jdideal->get(
							'transferStatus', $defaultStatus
						);
						break;
					default:
						$orderData['order_status'] = $jdideal->get(
							'openStatus', $defaultStatus
						);
						break;
				}

				$orderStatusName = $extensionAddon->getOrderStatusName(
					$orderData
				);
				$orderLink       = $extensionAddon->getOrderLink(
					$orderData['order_id'], $orderData['order_number']
				);

				// Send out the emails if needed
				try
				{
					$jdideal->log('Send customer change status email', $logId);
					$jdideal->log(json_encode($orderData), $logId);
					$this->emailCustomerChangeStatus(
						$jdideal,
						$responseStatus,
						$orderData,
						$orderStatusName,
						$orderLink
					);
				}
				catch (Exception $exception)
				{
					$jdideal->log($exception->getMessage(), $logId);
				}

				try
				{
					$jdideal->log('Send admin order payment email', $logId);
					$this->emailAdminOrderPayment(
						$jdideal,
						$transactionDetails,
						$transactionStatus,
						$orderData,
						$orderStatusName,
						$transactionID
					);
				}
				catch (Exception $exception)
				{
					$jdideal->log($exception->getMessage(), $logId);
				}
			}

			if (!$recurring)
			{
				// Build the notify URL for the extension
				$redirect = $transactionDetails->notify_url
					?: $transactionDetails->return_url;

				if (!$resultData['isValid'])
				{
					$jdideal->log('Result data is not valid', $logId);

					$redirect = $transactionDetails->cancel_url
						?: $transactionDetails->return_url;
				}

				// Complete the redirect URL
				if (strpos($redirect, '?') !== false)
				{
					$redirect .= '&transactionId=' . $transactionID;
				}
				else
				{
					$redirect .= '?transactionId=' . $transactionID;
				}

				$redirect .= '&pid=' . $transactionDetails->pid;

				// Call the extension to update the status if we are the payment provider calling
				$jdideal->log(
					'Notifying extension on URL: ' . $redirect, $logId
				);

				try
				{
					$cookieFile = $config->get('tmp_path') . '/cookie' . $logId . '.txt';
					$options    = new Registry;
					$options->set(
						'transport.curl',
						[
							CURLOPT_COOKIEJAR  => $cookieFile,
							CURLOPT_COOKIEFILE => $cookieFile,
						]
					);
					$http         = HttpFactory::getHttp(
						$options, ['curl', 'stream']
					);
					$notifyMethod = 'get';

					if (method_exists($extensionAddon, 'notifyMethod'))
					{
						$notifyMethod = $extensionAddon->notifyMethod();
					}

					$jdideal->log('Notify method: ' . $notifyMethod, $logId);

					switch ($notifyMethod)
					{
						case 'post':
							$data = array(
								'trans'        => $transactionDetails->trans,
								'order_number' => $transactionDetails->order_number,
								'amount'       => $transactionDetails->amount,
								'result'       => $transactionDetails->result,
							);

							$httpResponse = $http->post($redirect, $data);
							break;
						case 'get':
						default:
							$uri = Uri::getInstance();
							$jdideal->log(
								'Coming in on  ' . $uri->toString(), $logId
							);
							$url = str_ireplace(
								'/cli/notify.php',
								'',
								$uri->toString(
									['scheme', 'user', 'pass', 'host', 'port', 'path']
								)
							);
							$url .= '/index.php?option=com_jdidealgateway&task=checkideal.request&format=raw&redirect='
								. base64_encode($redirect);
							$jdideal->log(
								'Calling RO Payments on  ' . $url, $logId
							);
							$httpResponse = $http->get($url);
							break;
					}

					File::delete($cookieFile);

					$jdideal->log(
						'Received HTTP status ' . $httpResponse->code, $logId
					);

					if ($httpResponse->code === 500)
					{
						$jdideal->log($httpResponse->body, $logId);
					}

					// Get the new transaction details as they may have changed
					$transactionDetails = $jdideal->getDetails(
						$transactionDetails->id, 'id', true
					);
				}
				catch (Exception $exception)
				{
					$jdideal->log(
						'Caught an error notifying extension: '
						. $exception->getMessage(), $logId
					);
				}
			}

			if ($isCustomer)
			{
				$redirect = $this->getCustomerRedirect(
					$jdideal, $transactionDetails
				);

				$jdideal->log('Redirecting customer to: ' . $redirect, $logId);
			}

			$extensionAddon->callBack(
				array_merge($resultData, (array) $transactionDetails)
			);
		}

		if (!$redirect)
		{
			// Get the redirect URL
			$redirect = $this->getCustomerRedirect(
				$jdideal, $transactionDetails
			);

			$jdideal->log('Redirecting customer to: ' . $redirect, $logId);
		}

		if ($processed)
		{
			$jdideal->log('Setting payment as processed', $logId);
			$jdideal->setProcessed(1, $logId);
		}

		$return               = [];
		$return['isCustomer'] = $isCustomer;
		$return['url']        = $redirect;
		$return['message']    = '';
		$return['level']      = $errorLevel;
		$return['status']     = $status ? 'OK' : 'NOK';

		return $return;
	}

	/**
	 * Check if a transaction exists with this payment ID.
	 *
	 * @param   string  $paymentId  The payment ID to check
	 *
	 * @return  boolean  True if the transaction exists | False otherwise.
	 *
	 * @since   6.4.0
	 */
	private function doesTransactionExist(string $paymentId): bool
	{
		/** @var TableLog $logTable */
		$logTable = Table::getInstance('Log', 'Table');
		$logTable->load(
			[
				'paymentId' => $paymentId,
			]
		);

		return ((int) $logTable->id) > 0;
	}

	/**
	 * Create a new transaction log.
	 *
	 * @param   int     $logId      The ID of the existing transaction log
	 * @param   string  $paymentId  The payment ID to check
	 *
	 * @return  integer  The new log ID.
	 *
	 * @since   6.4.0
	 */
	private function createTransaction(int $logId, string $paymentId): int
	{
		/** @var TableLog $logTable */
		$logTable = Table::getInstance('Log', 'Table');
		$logTable->load($logId);
		$logTable->set('id');
		$logTable->set('result');
		$logTable->set(
			'date_added', HTMLHelper::_('date', 'now', 'Y-m-d H:i:s', false)
		);
		$logTable->set('paymentId', $paymentId);
		$logTable->set('processed', 0);
		$logTable->set('pid', '');
		$logTable->set('history', '');
		$logTable->store(true);

		return (int) $logTable->id;
	}

	/**
	 * Trigger a plugin.
	 *
	 * @param   string  $trigger  The name of the plugin trigger
	 * @param   array   $data     The data to send to the plugin
	 *
	 * @return  void
	 *
	 * @since   6.4.0
	 * @throws  Exception
	 */
	private function triggerPlugin(string $trigger, array $data = []): void
	{
		PluginHelper::importPlugin('jdideal');

		if (JVERSION < 4)
		{
			$dispatcher = JEventDispatcher::getInstance();
			$dispatcher->trigger($trigger, [$data]);
		}
		else
		{
			Factory::getApplication()->triggerEvent(
				$trigger, [$data]
			);
		}
	}

	/**
	 * Write information to the error log.
	 *
	 * @param   string  $message  The exception message to write to the log
	 *
	 * @return  void
	 *
	 * @since   4.6.0
	 * @throws  Exception
	 */
	public function writeErrorLog(string $message): void
	{
		Log::addLogger(
			array(
				'text_file' => 'com_jdidealgateway.errors.php',
			),
			Log::ERROR,
			array('com_jdidealgateway')
		);

		$message = $message . "\r\n"
			. 'Request method: ' . $_SERVER['REQUEST_METHOD'] . "\r\n";

		$input = $this->app->input;

		// Check for POST variables
		$getArray = $input->get->getArray();
		$get      = array();

		foreach ($getArray as $name => $value)
		{
			$get[] = $name . '=' . $input->get->get($name);
		}

		$message .= 'GET variables: ' . implode('&', $get) . "\r\n";

		$postArray = $input->post->getArray();
		$post      = array();

		foreach ($postArray as $name => $value)
		{
			$post[] = $name . '=' . $input->post->get($name);
		}

		$message .= 'POST variables: ' . implode('&', $post) . "\r\n";

		$payload = file_get_contents('php://input');

		$message .= 'Payload body: ' . $payload . "\r\n";

		if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
		{
			$message .= 'User agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
		}

		Log::add($message, Log::ERROR, 'com_jdidealgateway');
	}

	/**
	 * Send the email for a not OK status.
	 *
	 * @param   Gateway  $jdideal    The JdidealGateway class.
	 * @param   object   $details    An array with transaction details.
	 * @param   array    $orderData  An array with result status.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	private function emailResultNOK(
		Gateway $jdideal,
		$details,
		$orderData
	): void {
		if ($jdideal->get('admin_status_failed'))
		{
			// Inform the admin
			// Get the body from the database if available
			$mailTemplate = $jdideal->getMailBody('admin_payment_failed');

			if ($mailTemplate)
			{
				$find      = [];
				$find[]    = '{ORDERNR}';
				$find[]    = '{ORDERID}';
				$find[]    = '{BEDRAG}';
				$find[]    = '{USER_EMAIL}';
				$replace   = array();
				$replace[] = $orderData['order_number'];
				$replace[] = $orderData['order_id'];
				$replace[] = number_format($details->amount, 2, ',', '.');
				$replace[] = $orderData['user_email'];
				$body      = str_ireplace($find, $replace, $mailTemplate->body);
				$subject   = str_ireplace(
					$find, $replace, $mailTemplate->subject
				);
				$html      = true;
			}
			else
			{
				$subject = sprintf(
					Text::_('COM_ROPAYMENTS_MAIL_PAYMENT_STATUS'),
					$this->from,
					Text::_('COM_ROPAYMENTS_MAIL_STATUS_REQUEST_FAILED'),
					$orderData['order_number']
				);
				$body    = Text::_('COM_ROPAYMENTS_MAIL_INTRO') . "\n\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_IDEAL_RESULT'),
						strtoupper(
							Text::_('COM_ROPAYMENTS_MAIL_STATUS_REQUEST_FAILED')
						)
					) . "\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_TRANSACTION_FOR'),
						$this->from, $this->siteUrl
					) . "\n";
				$body    .= "-----------------------------------------------------------\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_EMAIL_CUSTOMER'),
						$orderData['user_email']
					) . "\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_ORDER_ID'),
						$orderData['order_number']
					) . "\n";
				$html    = false;
			}

			$recipients = explode(',', $jdideal->get('jdidealgateway_emailto'));

			if ($recipients)
			{
				foreach ($recipients as $recipient)
				{
					$this->mail->clearAddresses();
					$this->mail->sendMail(
						$this->from, $this->fromName, $recipient, $subject,
						$body, $html
					);
				}
			}
		}
	}

	/**
	 * Send the customer change status email.
	 *
	 * @param   Gateway  $jdideal          The JdidealGateway class.
	 * @param   string   $result           The payment result.
	 * @param   array    $orderData        An array with result status.
	 * @param   string   $orderStatusName  The order status name.
	 * @param   string   $orderLink        The link to the order.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 * @throws  Exception
	 *
	 */
	private function emailCustomerChangeStatus(
		Gateway $jdideal,
		$result,
		$orderData,
		$orderStatusName,
		$orderLink
	) {
		if ($jdideal->get('customer_change_status'))
		{
			$recipient    = $orderData['user_email'];
			$mailTemplate = $jdideal->getMailBody('customer_change_status');

			if ($mailTemplate)
			{
				$subject   = $mailTemplate->subject;
				$find      = array();
				$find[]    = '{ORDERNR}';
				$find[]    = '{ORDERID}';
				$find[]    = '{STATUS_NAME}';
				$find[]    = '{ORDER_LINK}';
				$replace   = array();
				$replace[] = $orderData['order_number'];
				$replace[] = $orderData['order_id'];
				$replace[] = $orderStatusName;
				$replace[] = $orderLink ? Route::_($this->siteUrl . $orderLink)
					: '';
				$body      = str_ireplace($find, $replace, $mailTemplate->body);
				$html      = true;
			}
			else
			{
				$subject = sprintf(
					Text::_('COM_ROPAYMENTS_MAIL_PAYMENT_STATUS'),
					$this->fromName,
					ucfirst(Text::_($result)),
					$orderData['order_number']
				);
				$body    = Text::_('COM_ROPAYMENTS_MAIL_INTRO') . "\n\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_STATUS_CHANGED'),
						$orderData['order_number']
					) . "\n";
				$body    .= "-------------------------------------------------------------------------------------------------------\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_NEW_STATUS'),
						$orderStatusName
					) . "\n";
				$body    .= "-------------------------------------------------------------------------------------------------------\n";
				$body    .= "\n\n";

				if ($orderLink)
				{
					$body .= Text::_('COM_ROPAYMENTS_MAIL_CLICK_BROWSER_LINK')
						. "\n";
					$body .= $this->siteUrl . $orderLink . "\n";
					$body .= "\n\n";
					$body .= "-------------------------------------------------------------------------------------------------------\n";
				}

				$body .= $this->fromName . "\n";
				$body .= $this->siteUrl . "\n";
				$body .= $this->from;
				$html = false;
			}

			$this->mail->clearAddresses();
			$this->mail->sendMail(
				$this->from, $this->fromName, $recipient, $subject, $body, $html
			);
		}
	}

	/**
	 * Send the administrator change status email.
	 *
	 * @param   Gateway  $jdideal          The JdidealGateway class.
	 * @param   object   $details          An array with transaction details.
	 * @param   array    $result           An array with result data.
	 * @param   array    $orderData        An array with result status.
	 * @param   string   $orderStatusName  The order status name.
	 * @param   string   $transactionID    The link to the order.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 *
	 */
	private function emailAdminOrderPayment(
		Gateway $jdideal,
		$details,
		$result,
		$orderData,
		$orderStatusName,
		$transactionID
	) {
		if ($jdideal->get('admin_order_payment'))
		{
			// Get the body from the database if available
			$mailTemplate = $jdideal->getMailBody('admin_order_payment');

			if ($mailTemplate)
			{
				$find      = array();
				$find[]    = '{ORDERNR}';
				$find[]    = '{ORDERID}';
				$find[]    = '{BEDRAG}';
				$find[]    = '{STATUS}';
				$find[]    = '{STATUS_NAME}';
				$find[]    = '{TRANSACTION_ID}';
				$find[]    = '{USER_EMAIL}';
				$find[]    = '{CONSUMERACCOUNT}';
				$find[]    = '{CONSUMERIBAN}';
				$find[]    = '{CONSUMERBIC}';
				$find[]    = '{CONSUMERNAME}';
				$find[]    = '{CONSUMERCITY}';
				$find[]    = '{CARD}';
				$replace   = array();
				$replace[] = $orderData['order_number'];
				$replace[] = $orderData['order_id'];
				$replace[] = number_format($details->amount, 2, ',', '.');
				$replace[] = Text::_(strtoupper($result['suggestedAction']));
				$replace[] = Text::_($orderStatusName);
				$replace[] = $transactionID;
				$replace[] = $orderData['user_email'];

				if (!empty($result['consumer']))
				{
					$replace[] = array_key_exists(
						'consumerAccount',
						$result['consumer']
					) ? $result['consumer']['consumerAccount'] : '';
					$replace[] = array_key_exists(
						'consumerIban',
						$result['consumer']
					) ? $result['consumer']['consumerIban'] : '';
					$replace[] = array_key_exists(
						'consumerBic',
						$result['consumer']
					) ? $result['consumer']['consumerBic'] : '';
					$replace[] = array_key_exists(
						'consumerName',
						$result['consumer']
					) ? $result['consumer']['consumerName'] : '';
					$replace[] = array_key_exists(
						'consumerCity',
						$result['consumer']
					) ? $result['consumer']['consumerCity'] : '';
				}
				else
				{
					$replace[] = '';
					$replace[] = '';
					$replace[] = '';
					$replace[] = '';
					$replace[] = '';
				}

				$replace[] = $result['card'] ?: '';
				$body      = str_ireplace($find, $replace, $mailTemplate->body);
				$subject   = str_ireplace(
					$find, $replace, $mailTemplate->subject
				);
				$html      = true;
			}
			else
			{
				$subject = sprintf(
					Text::_('COM_ROPAYMENTS_MAIL_PAYMENT_STATUS'),
					$this->fromName,
					ucfirst(Text::_($result['suggestedAction'])),
					$orderData['order_number']
				);
				$body    = Text::_('COM_ROPAYMENTS_MAIL_INTRO') . "\n\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_IDEAL_RESULT'),
						ucfirst(Text::_($result['suggestedAction']))
					) . "\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_TRANSACTION_FOR'),
						$this->fromName,
						$this->siteUrl
					) . "\n";
				$body    .= "-----------------------------------------------------------\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_TRANSACTION_ID'),
						$transactionID
					) . "\n";
				$body    .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_EMAIL_CUSTOMER'),
						$orderData['user_email']
					) . "\n";

				if (!empty($result['consumer']))
				{
					if (!empty($result['consumer']['consumerAccount']))
					{
						$body .= sprintf(
								Text::_('COM_ROPAYMENTS_MAIL_ACCOUNT_CUSTOMER'),
								$result['consumer']['consumerAccount']
							) . "\n";
					}

					if (!empty($result['consumer']['consumerIban']))
					{
						$body .= sprintf(
								Text::_('COM_ROPAYMENTS_MAIL_IBAN_CUSTOMER'),
								$result['consumer']['consumerIban']
							) . "\n";
					}

					if (!empty($result['consumer']['consumerBic']))
					{
						$body .= sprintf(
								Text::_('COM_ROPAYMENTS_MAIL_BIC_CUSTOMER'),
								$result['consumer']['consumerBic']
							) . "\n";
					}

					if (!empty($result['consumer']['consumerName']))
					{
						$body .= sprintf(
								Text::_('COM_ROPAYMENTS_MAIL_NAME_CUSTOMER'),
								$result['consumer']['consumerName']
							) . "\n";
					}

					if (!empty($result['consumer']['consumerCity']))
					{
						$body .= sprintf(
								Text::_('COM_ROPAYMENTS_MAIL_CITY_CUSTOMER'),
								$result['consumer']['consumerCity']
							) . "\n";
					}
				}

				$body .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_ORDER_ID'),
						$orderData['order_number']
					) . "\n";
				$body .= sprintf(
						Text::_('COM_ROPAYMENTS_MAIL_ORDER_STATUS'),
						$orderStatusName
					) . "\n";

				if ($result['card'])
				{
					$body .= sprintf(
							Text::_('COM_ROPAYMENTS_MAIL_ORDER_CARD'),
							$result['card']
						) . "\n";
				}

				$html = false;
			}

			// Load list of users to inform
			$recipients = explode(',', $jdideal->get('jdidealgateway_emailto'));

			if ($recipients)
			{
				foreach ($recipients as $recipient)
				{
					$this->mail->clearAddresses();
					$this->mail->sendMail(
						$this->from, $this->fromName, $recipient, $subject,
						$body, $html
					);
				}
			}
		}
	}

	/**
	 * Build the return URL for the customer.
	 *
	 * @param   Gateway  $jdideal             The JdidealGateway class.
	 * @param   object   $transactionDetails  The details of the transaction.
	 *
	 * @return  string  The URL to send the customer to.
	 *
	 * @since   4.0.0
	 */
	private function getCustomerRedirect(Gateway $jdideal, $transactionDetails)
	{
		// Transaction already processed, build the return URL for the extension
		$redirect = $transactionDetails->cancel_url
			?: $transactionDetails->return_url;

		if ($jdideal->isValid($transactionDetails->result))
		{
			$redirect = $transactionDetails->return_url;
		}

		// Complete the redirect URL
		if (strpos($redirect, '?') !== false)
		{
			$redirect .= '&transactionId=' . $transactionDetails->trans;
		}
		else
		{
			$redirect .= '?transactionId=' . $transactionDetails->trans;
		}

		return $redirect . '&pid=' . $transactionDetails->pid;
	}

	/**
	 * Check who is knocking at the door.
	 *
	 * @return  boolean  True if it is the PSP otherwise false if it is the customer.
	 *
	 * @since   4.0
	 *
	 * @throws  Exception
	 */
	public function whoIsCalling(): bool
	{
		$jdideal = new Gateway;
		$input   = $this->app->input;

		$notifier = $jdideal->loadNotifier($jdideal, $input);
		$callback = false;

		if ($notifier)
		{
			$callback = $notifier->isCustomer();
		}

		return $callback;
	}
}
