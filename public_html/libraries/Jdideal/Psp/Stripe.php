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
use InvalidArgumentException;
use Jdideal\Gateway;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Stripe Payments
 *
 * @package     RO_Payments
 * @subpackage  Stripe
 *
 * @since       8.0.0
 * @link        https://github.com/stripe/stripe-php
 */
class Stripe extends Psp implements PspInterface
{
	/**
	 * THe payload received from Stripe
	 *
	 * @var    string|false
	 * @since  8.0.0
	 */
	protected $payload = '';

	/**
	 * Construct the payment reference.
	 *
	 * @param   Input  $input  The input object.
	 *
	 * @since   8.0.0
	 */
	public function __construct(Gateway $gateway, $input)
	{
		parent::__construct($gateway, $input);

		$this->payload = file_get_contents('php://input');
		$this->validateRequest($gateway->get('endpointSecret'));

		$this->isCustomer = $input->get('output', '') === 'customer';

		if ($this->isCustomer)
		{
			$this->data['transactionId'] = $input->get('payment_intent');
		}
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
	 * @throws  InvalidArgumentException|ApiErrorException
	 * @throws  Exception
	 * @see     https://stripe.com/docs/api/payment_intents/create
	 */
	public function getForm(Gateway $jdideal, $data): array
	{
		$output = [];
		$logId  = $data->logid;
		$stripe = new StripeClient(
			[
				'api_key' => $jdideal->get('secretKey'),
			]
		);

		$description         = $jdideal->replacePlaceholders($logId, $jdideal->get('description'));
		$description         = substr($description, 0, 32);
		$addon               = $jdideal->getAddon($data->origin);
		$customer            = $addon->getCustomerInformation($data->order_id);
		$output['returnUrl'] = Uri::root() . 'cli/notify.php?output=customer&pid=' . $data->pid;

		$params = [
			'amount'                    => $data->amount * 100,
			'currency'                  => $data->currency ?: 'EUR',
			'description'               => $description,
			'automatic_payment_methods' => ['enabled' => true],
		];

		if ($paymentMethods = $jdideal->get('payment', []))
		{
			// Get the payment method, plugin overrides component
			if (isset($data->payment_method) && $data->payment_method)
			{
				$paymentMethods = [strtolower($data->payment_method)];
			}

			if (!in_array('all', $paymentMethods, true))
			{
				$params['payment_method_types'] = $paymentMethods;
				unset($params['automatic_payment_methods']);
			}
		}

		if (isset($customer['billing']->email))
		{
			$params['receipt_email'] = $customer['billing']->email;
		}

		$paymentIntent = $stripe->paymentIntents->create($params);

		$output['intent'] = $paymentIntent;

		$jdideal->setTrans($paymentIntent->id, $logId);
		$jdideal->setPaymentId($paymentIntent->client_secret, $logId);

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
			'card'            => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_CREDITCARD'),
			'alipay'          => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_ALIPAY'),
			'au_becs_debit'   => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_AU_BECS_DEBIT'),
			'bacs_debit'      => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_BACS_DEBIT'),
			'bancontact'      => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_BANCONTACT'),
			'boleto'          => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_BOLETO'),
			'eps'             => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_EPS'),
			'fpx'             => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_FPX'),
			'giropay'         => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_GIROPAY'),
			'grabpay'         => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_GRABPAY'),
			'ideal'           => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_IDEAL'),
			'klarna'          => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_KLARNA'),
			'konbini'         => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_KONBINI'),
			'oxxo'            => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_OXXO'),
			'p24'             => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_PRZELEWY24'),
			'paynow'          => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_PAYNOW'),
			'sepa_debit'      => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_SEPADIRECTDEBIT'),
			'sofort'          => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_SOFORT'),
			'us_bank_account' => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_US_BANK_ACCOUNT'),
			'wechat_pay'      => Text::_('COM_JDIDEALGATEWAY_PAYMENT_METHOD_WECHAT_PAY'),
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
		$stripe         = new StripeClient($jdideal->get('secretKey'));
		/** @var PaymentIntent $paymentIntent */
		$paymentIntent = $this->data['paymentIntent'];

		$jdideal->log('Received payment status: ' . $paymentIntent->type, $logId);

		switch ($paymentIntent->type)
		{
			case 'payment_intent.canceled':
				$status['suggestedAction'] = 'CANCELLED';
				$status['error_message']   = $paymentIntent->data->object->last_payment_error->message;
				break;
			case 'payment_intent.payment_failed':
				$status['suggestedAction'] = 'FAILURE';
				$status['error_message']   = $paymentIntent->data->object->last_payment_error->message;
				break;
			case 'payment_intent.succeeded':
				$status['suggestedAction'] = 'SUCCESS';
				break;
			default:
				$status['suggestedAction'] = 'OPEN';
				$status['error_message']   = $paymentIntent->data->object->last_payment_error->message;
				break;
		}

		try
		{
			$paymentMethodId = $paymentIntent->data->object->payment_method ??
				$paymentIntent->data->object->last_payment_error->payment_method->id;
			$paymentMethod   = $stripe->paymentMethods->retrieve($paymentMethodId);
			$status['card']  = $paymentMethod->type;

			if ($paymentMethod->type === 'card')
			{
				$status['card'] = $paymentMethod->card->brand;
			}

			$jdideal->setTransactionDetails($status['card'], 0, $logId);
		}
		catch (Exception $exception)
		{
			$jdideal->log('Payment method not retrieved', $logId);
			$jdideal->log($exception->getMessage(), $logId);
		}

		$status['consumer'] = [];

		$status['consumer']['consumerAccount'] = '';
		$status['consumer']['consumerIban']
		                                       = $paymentIntent->data->object->charges->payment_method_details->{$paymentIntent->data->object->charges->type}->iban_last4
			?? '';
		$status['consumer']['consumerBic']
		                                       = $paymentIntent->data->object->charges->payment_method_details->{$paymentIntent->data->object->charges->type}->bic
			?? '';
		$status['consumer']['consumerName']
		                                       = $paymentIntent->data->object->charges->payment_method_details->{$paymentIntent->data->object->charges->type}->verified_name
			?? '';
		$status['consumer']['consumerCity']    = '';

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
		return false;
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
	 * Validate the request coming in from Stripe.
	 *
	 * @return  void
	 *
	 * @since   8.0.0
	 */
	private function validateRequest(string $endpointSecret): void
	{
		$stripeHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

		if (!$stripeHeader || !$this->payload)
		{
			return;
		}

		try
		{
			$event = Webhook::constructEvent(
				$this->payload, $stripeHeader, $endpointSecret
			);
		}
		catch (\UnexpectedValueException $e)
		{
			// Invalid payload
			http_response_code(400);
			exit();
		}
		catch (SignatureVerificationException $e)
		{
			// Invalid signature
			http_response_code(400);
			exit();
		}

		$this->data['paymentIntent'] = $event;
		$this->data['transactionId'] = $event->data->object->id;
	}

	/**
	 * Check if the request should be processed.
	 *
	 * @return  boolean  True if the process should be processed | False otherwise.
	 *
	 * @since   8.0.0
	 */
	public function processRequest(): bool
	{
		/** @var PaymentIntent $paymentIntent */
		$paymentIntent = $this->data['paymentIntent'] ?? [];

		if (empty($paymentIntent))
		{
			return true;
		}

		if ($paymentIntent->type === 'payment_intent.requires_action')
		{
			return false;
		}

		return true;
	}
}
