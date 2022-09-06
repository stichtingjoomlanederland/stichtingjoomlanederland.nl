<?php
/**
 * @package     JDiDEAL
 * @subpackage  Ing
 *
 * @author      Roland Dalmulder <contact@rolandd.com>
 * @copyright   Copyright (C) 2009 - 2022 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Jdideal\Psp;

use ErrorException;
use Exception;
use iDEALConnector\Configuration\DefaultConfiguration;
use iDEALConnector\Entities\DirectoryResponse;
use iDEALConnector\Entities\Transaction;
use iDEALConnector\Exceptions\iDEALException;
use iDEALConnector\Exceptions\SecurityException;
use iDEALConnector\Exceptions\SerializationException;
use iDEALConnector\Exceptions\ValidationException;
use iDEALConnector\iDEALConnector;
use iDEALConnector\Log\DefaultLog;
use InvalidArgumentException;
use Jdideal\Gateway;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Input\Input;
use RuntimeException;

defined('_JEXEC') or die;

/**
 * iDEAL Advanced processor.
 *
 * @package     JDiDEAL
 * @subpackage  Ing
 * @since       8.0.0
 */
class Ing
{
	/**
	 * Input processor
	 *
	 * @var    Input
	 * @since  8.0.0
	 */
	private $input;

	/**
	 * Array with return data from Mollie
	 *
	 * @var    array
	 * @since  8.0.0
	 */
	private $data;

	/**
	 * Construct the payment reference.
	 *
	 * @param   Input  $input  The input object.
	 *
	 * @since   8.0.0
	 */
	public function __construct(Input $input)
	{
		// Set the input
		$this->input = $input;

		// Put the return data in an array, data is constructed as name=value
		$this->data['transactionId'] = $input->get('trxid');
		$this->data['logId']         = $input->get('ec');
	}

	/**
	 * Returns a list of available payment methods.
	 *
	 * @return  array  List of available payment methods.
	 *
	 * @since   8.0.0
	 */
	public function getAvailablePaymentMethods()
	{
		return [
			'ideal' => 'iDEAL',
		];
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
	 * @throws  Exception
	 * @throws  ErrorException
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 */
	public function getForm(Gateway $jdideal, $data)
	{
		$banks = [];

		try
		{
			$banks = $this->getBanks();
		}
		catch (Exception $exception)
		{
			$jdideal->log('Errorcode: ' . $exception->getCode(), $data->logid);
			$jdideal->log('Error Message: ' . $exception->getMessage(), $data->logid);
		}

		$output             = [];
		$output['banks']    = $banks;
		$output['redirect'] = $jdideal->get('redirect', 'wait');

		return $output;
	}

	/**
	 * Get a list of banks.
	 *
	 * @return  array  List of banks.
	 *
	 * @since   8.0.0
	 * @throws  SerializationException
	 * @throws  ValidationException
	 * @throws  iDEALException
	 * @throws  SecurityException|Exception
	 */
	public function getBanks(): array
	{
		try
		{
			$iDEALConnector = $this->getConnector();

			/* @var $response DirectoryResponse */
			$response   = $iDEALConnector->getIssuers();
			$issuerList = [];

			foreach ($response->getCountries() as $country)
			{
				$countryNames = $country->getCountryNames();

				foreach ($country->getIssuers() as $issuer)
				{
					$issuerList[$countryNames]['items'][] = HTMLHelper::_('select.option', $issuer->getId(),
						$issuer->getName());
				}
			}

			return $issuerList;
		}
		catch (Exception $exception)
		{
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'error');
		}

		return [];
	}

	/**
	 * Get the log ID.
	 *
	 * @return  int  The ID of the log.
	 *
	 * @since   8.0.0
	 *
	 * @throws  RuntimeException
	 */
	public function getLogId()
	{
		if (!array_key_exists('logId', $this->data) || empty($this->data['logId']))
		{
			throw new RuntimeException(Text::_('COM_ROPAYMENTS_NO_LOGID_FOUND'));
		}

		return $this->data['logId'];
	}

	/**
	 * Get the transaction ID.
	 *
	 * @return  int  The ID of the transaction.
	 *
	 * @since   8.0.0
	 *
	 * @throws  RuntimeException
	 */
	public function getTransactionId()
	{
		if (!array_key_exists('transactionId', $this->data))
		{
			throw new RuntimeException(Text::_('COM_ROPAYMENTS_NO_TRANSACTIONID_FOUND'));
		}

		// Get the transaction ID
		return $this->data['transactionId'];
	}

	/**
	 * Send payment to iDEAL Advanced.
	 *
	 * @param   Gateway  $jdideal  An instance of JdIdealGateway.
	 *
	 * @return  void.
	 *
	 * @since   8.0.0
	 *
	 * @throws  Exception
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 */
	public function sendPayment(Gateway $jdideal)
	{
		$app = Factory::getApplication();

		// Check if we have a selected bank
		$issuerID = $this->input->get('banks', false);

		// Load the stored data
		$logId   = $this->input->get('logid');
		$details = $jdideal->getDetails($logId);

		if ($issuerID && is_object($details))
		{
			try
			{
				$iDEALConnector = $this->getConnector();

				// Check if the order number meets the iDEAL requirement
				$orderNumber = $jdideal->get('orderNumber', 'order_number');
				$pattern     = '/[a-zA-Z0-9]+/';
				preg_match($pattern, $orderNumber, $matches);
				$purchaseId = substr($details->order_id, 0, 16);

				if ($matches[0] === $orderNumber)
				{
					$purchaseId = substr($orderNumber, 0, 16);
				}

				// Clean out any invalid characters
				$pattern    = '/[.]+/';
				$purchaseId = preg_replace($pattern, '', $purchaseId);

				$amount = (float) str_ireplace(',', '.', $details->amount);

				$jdideal->log('Amount: ' . $amount, $logId);

				$jdideal->setCurrency('EUR', $logId);

				// Replace some predefined values
				$description       = $jdideal->replacePlaceholders($logId, $jdideal->get('IDEAL_DESCRIPTION'));
				$description       = substr($description, 0, 32);
				$entranceCode      = substr($details->id, 0, 40);
				$configuration     = $iDEALConnector->getConfiguration();
				$expirationPeriod  = $configuration->getExpirationPeriod();
				$merchantReturnUrl = $configuration->getMerchantReturnURL();

				$transaction = new Transaction(
					$amount,
					$description,
					$entranceCode,
					$expirationPeriod,
					$purchaseId
				);

				$response = $iDEALConnector->startTransaction($issuerID, $transaction, $merchantReturnUrl);

				$jdideal->setTrans($response->getTransactionID(), $logId);
				$jdideal->setPaymentId($response->getTransactionID(), $logId);
				$issuerAuthenticationURL = $response->getIssuerAuthenticationURL();

				$jdideal->log('Merchant Return URL: ' . $merchantReturnUrl, $logId);
				$jdideal->log('Send customer to URL: ' . $issuerAuthenticationURL, $logId);

				// Send the customer to the bank
				$app->redirect($issuerAuthenticationURL);
			}
			catch (Exception $exception)
			{
				// Log the errors
				$jdideal->log(Text::_('COM_ROPAYMENTS_TRANSACTION_REQUEST_FAILED'), $logId);
				$jdideal->log($exception->getMessage(), $logId);

				$consumerMsg = $exception->getMessage();

				if (method_exists($exception, 'getConsumerMessage'))
				{
					$jdideal->log($exception->getConsumerMessage(), $logId);
					$consumerMsg = $exception->getConsumerMessage();
				}

				// Transaction Request failed, inform the customer
				$url = $details->cancel_url ?: $details->return_url;

				if ($url)
				{
					$app->enqueueMessage(Text::sprintf('COM_ROPAYMENTS_IDEAL_ERROR_MESSAGE', $consumerMsg), 'error');
					$app->redirect($url . '&order_id=' . $details->order_id);
				}
				else
				{
					throw new RuntimeException(Text::sprintf('COM_ROPAYMENTS_IDEAL_ERROR_MESSAGE', $consumerMsg));
				}
			}
		}
		else
		{
			$app->enqueueMessage(Text::_('COM_ROPAYMENTS_NO_VALID_BANK_CHOICE'), 'error');
			$app->redirect('index.php');
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
	 * @param   Gateway  $jdideal  An instance of JdIdealGateway.
	 * @param   int      $logId    The ID of the transaction log.
	 *
	 * @return  array  Array of transaction details.
	 *
	 * @since   8.0.0
	 *
	 * @throws  Exception
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 */
	public function transactionStatus(Gateway $jdideal, $logId)
	{
		$transactionId = $this->getTransactionId();

		$iDEALConnector = $this->getConnector();

		$status                  = [];
		$status['transactionID'] = $transactionId;

		try
		{
			$result = $iDEALConnector->getTransactionStatus($transactionId);

			$status['isOK'] = true;
			$status['card'] = '';

			$jdideal->log($result->getStatus(), $logId);

			switch (strtolower($result->getStatus()))
			{
				case 'success':
					$status['suggestedAction'] = 'SUCCESS';
					$status['error_message']   = '';
					break;
				case 'cancelled':
					$status['suggestedAction'] = 'CANCELLED';
					$status['error_message']   = Text::_('COM_ROPAYMENTS_PAYMENT_CANCELLED');
					break;
				case 'expired':
					$status['suggestedAction'] = 'OPEN';
					$status['error_message']   = Text::_('COM_ROPAYMENTS_PAYMENT_EXPIRED');
					break;
				case 'failure':
					$status['suggestedAction'] = 'FAILURE';
					$status['error_message']   = Text::_('COM_ROPAYMENTS_PAYMENT_FAILURE');
					break;
				case 'open':
					$status['suggestedAction'] = 'OPEN';
					$status['error_message']   = Text::_('COM_ROPAYMENTS_PAYMENT_OPEN');
					break;
			}

			$status['consumer']['consumerAccount'] = '';
			$status['consumer']['consumerIban']    = $result->getConsumerIBAN();
			$status['consumer']['consumerBic']     = $result->getConsumerBIC();
			$status['consumer']['consumerName']    = $result->getConsumerName();
			$status['consumer']['consumerCity']    = '';

			$jdideal->log('IBAN: ' . $result->getConsumerIBAN(), $logId);
			$jdideal->log('BIC: ' . $result->getConsumerBIC(), $logId);
			$jdideal->log('Name: ' . $result->getConsumerName(), $logId);
			$jdideal->setTransactionDetails('iDEAL', 1, $logId);
		}
		catch (Exception $exception)
		{
			$jdideal->log($exception->getCode(), $logId);
			$jdideal->log($exception->getMessage(), $logId);
			$jdideal->log(Text::_('COM_ROPAYMENTS_NO_VALID_RESPONSE'), $logId);
			$status['isOK']            = true;
			$status['isAuthenticated'] = false;
			$status['suggestedAction'] = 'CANCELLED';
			$status['consumer']        = [];

			$status['error_message'] = $exception->getMessage();

			if (method_exists($exception, 'getConsumerMessage'))
			{
				$status['error_message'] = $exception->getConsumerMessage();
			}
		}

		return $status;
	}

	/**
	 * Check who is knocking at the door.
	 *
	 * @return  bool  True if it is the customer | False if it is the PSP.
	 *
	 * @since   8.0.0
	 */
	public function isCustomer()
	{
		return true;
	}

	/**
	 * Tell RO Payments if status can be checked based on customer.
	 *
	 * @return  boolean  True if user status can be used | False otherwise.
	 *
	 * @since   8.0.0
	 */
	public function canUseCustomerStatus()
	{
		return true;
	}

	/**
	 * Create the options to show on a checkout page.
	 *
	 * @param   Gateway  $jdideal        An instance of JdidealGateway.
	 * @param   string   $paymentMethod  The name of the chosen payment method.
	 *
	 * @return  array  List of select options.
	 *
	 * @since   8.0.0
	 *
	 * @throws Exception
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function getCheckoutOptions(Gateway $jdideal, string $paymentMethod)
	{
		$banks   = $this->getBanks();
		$country = array_key_first($banks);

		return $banks[$country]['items'];
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
		return true;
	}

	/**
	 * Get the iDEAL Connector instance.
	 *
	 * @return  iDEALConnector  The connector instance.
	 *
	 * @since   8.3.0
	 */
	private function getConnector(): iDEALConnector
	{
		require_once __DIR__ . '/Ing/Connector/iDEALConnector.php';
		$configPath = __DIR__ . '/Ing/Connector/config.conf';
		$config     = new DefaultConfiguration($configPath, false);

		return new  iDEALConnector($config, new DefaultLog($config->getLogLevel(), $config->getLogFile()));
	}
}
