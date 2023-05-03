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

use Jdideal\Gateway;

/**
 * The Payment Service Provider interface.
 *
 * @package  RO_Payments
 * @since    8.0.0
 */
interface PspInterface
{
	/**
	 * Prepare data for the form.
	 *
	 * @param   Gateway  $jdideal  An instance of JdidealGateway.
	 * @param   object   $data     An object with transaction information.
	 *
	 * @return  array  The data for the form.
	 *
	 * @since   8.0.0
	 */
	public function getForm(Gateway $jdideal, $data): array;

	/**
	 * Returns a list of available payment methods.
	 *
	 * @return  array  List of available payment methods.
	 *
	 * @since   8.0.0
	 */
	public function getAvailablePaymentMethods(): array;

	/**
	 * Get the log ID.
	 *
	 * @return  integer  The ID of the log.
	 *
	 * @since   8.0.0
	 */
	public function getLogId(): int;

	/**
	 * Send payment to Provider.
	 *
	 * @param   Gateway  $jdideal  An instance of \Jdideal\Gateway.
	 *
	 * @return  void
	 *
	 * @since   3.0.0
	 */
	public function sendPayment(Gateway $jdideal): void;

	/**
	 * Check the transaction status.
	 *
	 * @param   Gateway  $jdideal  An instance of JdidealGateway.
	 * @param   int      $logId    The ID of the transaction log.
	 *
	 * @return  array  Array of transaction details.
	 *
	 * @since   8.0.0
	 */
	public function transactionStatus(Gateway $jdideal, int $logId): array;

	/**
	 * Get the transaction ID.
	 *
	 * @return  string  The ID of the transaction.
	 *
	 * @since   8.0.0
	 */
	public function getTransactionId(): string;

	/**
	 * Check who is knocking at the door.
	 *
	 * @return  boolean  True if it is the customer | False if it is the PSP.
	 *
	 * @since   8.0.0
	 */
	public function isCustomer(): bool;

	/**
	 * Tell RO Payments if status can be checked based on customer.
	 *
	 * @return  boolean  True if user status can be used | False otherwise.
	 *
	 * @since   8.0.0
	 */
	public function canUseCustomerStatus(): bool;

	/**
	 * Tell RO Payments the bank must be called instead of the bank calling us
	 *
	 * @return  boolean  True if the bank must be called | False if the bank calls us.
	 *
	 * @since   8.0.0
	 */
	public function callHome(): bool;
}
