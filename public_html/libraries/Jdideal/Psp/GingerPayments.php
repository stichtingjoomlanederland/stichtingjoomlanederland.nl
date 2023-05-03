<?php
/**
 * @package     RO_Payments
 * @subpackage  GingerPayments
 *
 * @author      Roland Dalmulder <contact@rolandd.com>
 * @copyright   Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Jdideal\Psp;

defined('_JEXEC') or die;

use Exception;
use Ginger\Ginger;
use InvalidArgumentException;
use Jdideal\Gateway;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use RuntimeException;

/**
 * Ginger Payments
 *
 * @package     RO_Payments
 * @subpackage  GingerPayments
 *
 * @since       8.0.0
 * @link        https://github.com/gingerpayments/ginger-php
 * @link        https://dev.online.emspay.eu/rest-api
 */
class GingerPayments extends Psp implements PspInterface
{
	/**
	 * Construct the payment reference.
	 *
	 * @param   Input  $input  The input object.
	 *
	 * @since   8.0.0
	 */
	public function __construct(Gateway $gateway, Input $input)
	{
		parent::__construct($gateway, $input);

		$transactionId = $input->get('transactionId');

		if (empty($transactionId))
		{
			$transactionId = $input->get('order_id');
		}

		$this->data['transactionId'] = $transactionId;

		$this->isCustomer = $input->get('output', '') === 'customer';
	}

	/**
	 * Prepare data for the form.
	 *
	 * @param   Gateway  $jdideal  An instance of JdidealGateway.
	 * @param   object   $data     An object with transaction information.
	 *
	 * @return  array  The data for the form.
	 *
	 * @since   8.0.0
	 *
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 */
	public function getForm(Gateway $jdideal, $data): array
	{
		$options = [];
		$banks   = false;

		// Get the payment method, plugin overrides component
		if (isset($data->payment_method) && $data->payment_method)
		{
			$selected   = [];
			$selected[] = strtolower($data->payment_method);
		}
		else
		{
			$selected = $jdideal->get('payment', ['all']);

			// If there is no choice made, set the value empty
			if ($selected[0] === 'all')
			{
				$selected = array_flip($this->getAvailablePaymentMethods());
			}
		}

		$output             = [];
		$output['redirect'] = $jdideal->get('redirect', 'wait');

		foreach ($selected as $name)
		{
			switch ($name)
			{
				case 'ideal':
					$options[] = HTMLHelper::_(
						'select.option',
						'ideal',
						Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_IDEAL')
					);

					$ginger = Ginger::createClient($jdideal->get('apiUrl'), $jdideal->get('apiKey'));
					$banks  = $ginger->getIdealIssuers();

					$output['redirect'] = 'wait';
					break;
				default:
					if ($name)
					{
						$options[] = HTMLHelper::_(
							'select.option',
							$name,
							Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_' . str_replace(['-', '_'], '', $name))
						);
					}
					break;
			}
		}

		$output['payments'] = $options;

		if ($banks)
		{
			$output['banks'] = $banks;
		}

		return $output;
	}

	/**
	 * Returns a list of available payment methods.
	 *
	 * @return  array  List of available payment methods.
	 *
	 * @since   8.0.0
	 */
	public function getAvailablePaymentMethods(): array
	{
		return [
			'apple-pay'     => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_PAYPAL'),
			'bancontact'    => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_BANCONTACT'),
			'bank-transfer' => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_BANKTRANSFER'),
			'credit-card'   => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_CREDITCARD'),
			'ideal'         => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_IDEAL'),
			'google-pay'    => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_GOOGLEPAY'),
			'payconiq'      => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_PAYCONIQ'),
			'paypal'        => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_APPLEPAY'),
			'sofort'        => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_SOFORT'),
		];
	}

	/**
	 * Get the log ID.
	 *
	 * @return  integer  The ID of the log.
	 *
	 * @since   8.0.0
	 *
	 * @throws  RuntimeException
	 */
	public function getLogId(): int
	{
		$logId = false;

		if ($this->data['transactionId'])
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__jdidealgateway_logs'))
				->where($this->db->quoteName('trans') . ' = ' . $this->db->quote($this->data['transactionId']));
			$this->db->setQuery($query);

			$logId = $this->db->loadResult();
		}

		if (!$logId)
		{
			throw new RuntimeException(Text::_('COM_ROPAYMENTS_NO_LOGID_FOUND'));
		}

		return $logId;
	}

	/**
	 * Send payment to Provider.
	 *
	 * @param   Gateway  $jdideal  An instance of \Jdideal\Gateway.
	 *
	 * @return  void
	 *
	 * @since   8.0.0
	 *
	 * @throws  Exception
	 * @throws  RuntimeException
	 */
	public function sendPayment(Gateway $jdideal): void
	{
		$app     = Factory::getApplication();
		$logId   = $this->input->get('logid', 0, 'int');
		$ginger  = Ginger::createClient($jdideal->get('apiUrl'), $jdideal->get('apiKey'));
		$details = $jdideal->getDetails($logId);

		if (!is_object($details))
		{
			throw new RuntimeException(
				Text::sprintf('COM_ROPAYMENTS_NO_TRANSACTION_DETAILS', 'Ginger Payments', $logId)
			);
		}

		$notify_url = Uri::root() . 'cli/notify.php?output=customer';

		try
		{
			$description = $jdideal->replacePlaceholders($logId, $jdideal->get('description'));
			$description = substr($description, 0, 32);

			$paymentMethod = $this->input->get('payment');
			$jdideal->log('Selected card: ' . $paymentMethod, $logId);

			$orderNumber = $jdideal->get('orderNumber', 'order_number');
			$payload
			             = [
				'currency'          => $details->currency ?: 'EUR',
				'amount'            => $details->amount * 100,
				'merchant_order_id' => $details->$orderNumber,
				'description'       => $description,
				'return_url'        => $notify_url,
				'transactions'      => [
					[
						'payment_method'         => $paymentMethod,
						'payment_method_details' => [
							'issuer_id' => $this->input->get('banks'),
						],
					],
				],
			];

			$jdideal->setCurrency($payload['currency'], $logId);
			$jdideal->log(json_encode($payload), $logId);

			$order = $ginger->createOrder($payload);

			if (!$order)
			{
				throw new InvalidArgumentException(Text::_('COM_ROPAYMENTS_TRANSACTION_NOT_CREATED'));
			}

			$jdideal->setTrans($order['id'], $logId);

			$paymentUrl = $order['transactions'][0]['payment_url'];

			$this->logDetails($order, $jdideal, $logId);

			// Check if we need to send the customer to the bank
			if ($order['status'] === 'new' && $paymentUrl)
			{
				$jdideal->log('Send customer to URL: ' . $paymentUrl, $logId);
				$app->redirect($paymentUrl);
			}

			// No need for redirect e.g. bank transfer, go straight to the notify URL
			$app->redirect($notify_url . '&transactionId=' . $order['id']);
		}
		catch (RuntimeException $exception)
		{
			$jdideal->log('The payment could not be created.', $logId);
			$jdideal->log('Error: ' . $exception->getMessage(), $logId);
			$jdideal->log('Notify URL: ' . $notify_url, $logId);

			throw new RuntimeException($exception->getMessage());
		}
	}

	/**
	 * Check the transaction status.
	 *
	 * isOK            = Set if the validation is OK
	 * card            = The payment method used by the customer
	 * suggestedAction = The result of the transaction
	 * error_message   = An error message in case there is an error with the transaction
	 * consumer        = Array with info about the customer
	 *
	 * @param   Gateway  $jdideal  An instance of JdidealGateway.
	 * @param   int      $logId    The ID of the transaction log.
	 *
	 * @return  array  Array of transaction details.
	 *
	 * @since   8.0.0
	 *
	 * @throws  RuntimeException
	 */
	public function transactionStatus(Gateway $jdideal, int $logId): array
	{
		$status         = [];
		$status['isOK'] = true;
		$ginger         = Ginger::createClient($jdideal->get('apiUrl'), $jdideal->get('apiKey'));
		$transactionId  = $this->getTransactionId();
		$payment        = $ginger->getOrder($transactionId);

		$jdideal->log(json_encode($payment), $logId);

		$status['card'] = $payment['transactions'][0]['payment_method'];

		$jdideal->log('Received payment status: ' . $payment['status'], $logId);

		if (strtolower($payment['status']) !== 'success' && $status['card'] === 'bank-transfer')
		{
			$status['isOK']            = true;
			$status['error_message']   = '';
			$status['suggestedAction'] = 'TRANSFER';
			$status['reference']       = $payment['transactions'][0]['payment_method_details']['reference'];
			$status['consumer']        = [];

			$jdideal->setTransactionDetails(
				$status['card'],
				0,
				$logId,
				$payment['transactions'][0]['payment_method_details']['reference']
			);

			$jdideal->log('Payment reference: ' . $payment['transactions'][0]['payment_method_details']['reference'],
				$logId);
		}
		else
		{
			switch (strtolower($payment['status']))
			{
				case 'cancelled':
				case 'refunded':
				case 'charged_back':
					$status['suggestedAction'] = 'CANCELLED';
					break;
				case 'fail':
				case 'expired':
					$status['suggestedAction'] = 'FAILURE';
					break;
				case 'success':
				case 'completed':
					$status['suggestedAction'] = 'SUCCESS';
					break;
				case 'processing':
				default:
					$status['suggestedAction'] = 'OPEN';
					break;
			}

			$jdideal->setTransactionDetails($status['card'], 0, $logId);

			$status['consumer'] = (array) $payment['transactions'][0]['payment_method_details'];

			$status['consumer']['consumerAccount'] = $status['consumer']['consumer_account'] ?? '';
			$status['consumer']['consumerIban']    = $status['consumer']['consumer_iban'] ?? '';
			$status['consumer']['consumerBic']     = $status['consumer']['consumer_bic'] ?? '';
			$status['consumer']['consumerName']    = $status['consumer']['consumer_name'] ?? '';
			$status['consumer']['consumerCity']    = $status['consumer']['consumer_city'] ?? '';
		}

		return $status;
	}

	/**
	 * Get the transaction ID.
	 *
	 * @return  string  The ID of the transaction.
	 *
	 * @since   8.0.0
	 *
	 * @throws  RuntimeException
	 */
	public function getTransactionId(): string
	{
		if (!array_key_exists('transactionId', $this->data))
		{
			throw new RuntimeException(Text::_('COM_ROPAYMENTS_NO_TRANSACTIONID_FOUND'));
		}

		return $this->data['transactionId'];
	}

	/**
	 * Check who is knocking at the door.
	 *
	 * @return  boolean  True if it is the customer | False if it is the PSP.
	 *
	 * @since   8.0.0
	 */
	public function isCustomer(): bool
	{
		return $this->isCustomer;
	}

	/**
	 * Tell RO Payments if status can be checked based on customer.
	 *
	 * @return  boolean  True if user status can be used | False otherwise.
	 *
	 * @since   8.0.0
	 */
	public function canUseCustomerStatus(): bool
	{
		return true;
	}

	/**
	 * Tell RO Payments the bank must be called instead of the bank calling us
	 *
	 * @return  boolean  True if the bank must be called | False if the bank calls us.
	 *
	 * @since   8.0.0
	 */
	public function callHome(): bool
	{
		return false;
	}

	/**
	 * Test an iDEAL payment.
	 *
	 * BANKNL2Y = eWL Issuer Simulation
	 * BANKNL3Y = TEST iDEAL issuer
	 *
	 * @return  string Returns the payment status
	 *
	 * @since   8.0.0
	 */
	public function sendTestPayment(Gateway $jdideal, float $amount): string
	{
		$ginger = Ginger::createClient($jdideal->get('apiUrl'), $jdideal->get('apiKey'));
		$payload
		        = [
			'currency'          => 'EUR',
			'amount'            => $amount * 100,
			'merchant_order_id' => (string) time(),
			'description'       => 'Test payment of ' . $amount . ' euro',
			'return_url'        => Uri::root() . 'cli/notify.php?output=customer',
			'transactions'      => [
				[
					'payment_method'         => 'ideal',
					'payment_method_details' => [
						'issuer_id' => 'BANKNL3Y',
					],
				],
			],
		];

		$order      = $ginger->createOrder($payload);
		$paymentUrl = $order['transactions'][0]['payment_url'];
		$options    = new Registry;
		$http       = HttpFactory::getHttp($options, ['curl', 'stream']);

		$http->get($paymentUrl);
		$payment = $ginger->getOrder($order['id']);

		return $payment['status'];
	}
}
