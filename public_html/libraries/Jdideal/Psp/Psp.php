<?php
/**
 * @package    RO_Payments
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Jdideal\Psp;

use JDatabaseDriver;
use Jdideal\Gateway;
use Joomla\CMS\Factory;
use Joomla\Input\Input;

/**
 * Base class for payment service providers.
 *
 * @package  RO_Payments
 * @since    1.0.0
 */
class Psp
{
	/**
	 * Database driver
	 *
	 * @var    JDatabaseDriver
	 * @since  8.0.0
	 */
	protected $db;

	/**
	 * Input processor
	 *
	 * @var    Input
	 * @since  8.0.0
	 */
	protected $input;

	/**
	 * Array with return data
	 *
	 * @var    array
	 * @since  8.0.0
	 */
	protected $data = [];

	/**
	 * Set if the customer or PSP is calling
	 *
	 * @var    boolean
	 * @since  8.0.0
	 */
	protected $isCustomer = false;

	/**
	 * Construct the payment reference.
	 *
	 * @param   Gateway  $gateway  The Gateway class
	 * @param   Input    $input    The input object.
	 *
	 * @since   8.0.0
	 */
	public function __construct(Gateway $gateway, $input)
	{
		$this->input = $input;
		$this->db    = Factory::getDbo();
	}

	/**
	 * Log payment details.
	 *
	 * @param   array    $details  The details to log
	 * @param   Gateway  $jdideal  The RO Payments instance
	 * @param   int      $logId    The log ID
	 *
	 * @return  void
	 *
	 * @since   8.0.0
	 */
	protected function logDetails(array $details, Gateway $jdideal, int $logId): void
	{
		foreach ($details as $name => $value)
		{
			if (is_array($value) || is_object($value))
			{
				$this->logDetails($value, $jdideal, $logId);
			}

			if (is_string($value))
			{
				$jdideal->log($name . ': ' . $value, $logId);
			}
		}
	}
}
