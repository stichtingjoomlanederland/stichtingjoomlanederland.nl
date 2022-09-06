<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2022 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Jdideal\Gateway;
use Jdideal\Psp\GingerPayments;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;

defined('_JEXEC') or die;

/**
 * Logs controller.
 *
 * @package  JDiDEAL
 * @since    2.0
 */
class JdidealgatewayControllerAjax extends FormController
{
	/**
	 * Update the status of one or more log entries.
	 *
	 * @return  void
	 *
	 * @since   8.0.0
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 * @throws  Exception
	 */
	public function paymentTest(): void
	{
		$profileAlias = $this->input->getString('profileAlias', '');
		$amount       = $this->input->getInt('amount', 0);
		$message      = '';
		$error        = false;

		try
		{
			$jdideal = new Gateway($profileAlias);

			switch ($jdideal->psp)
			{
				case 'advanced':
					break;
				case 'buckaroo':
					break;
				case 'gingerpayments':
					$ginger = new GingerPayments($this->input);
					$result = $ginger->sendTestPayment($jdideal, $amount);
					break;
				case 'mollie':
					break;
				case 'onlinekassa':
				case 'sisow':
					break;
				case 'targetpay':
					break;
			}
		}
		catch (Exception $exception)
		{
			$message = $exception->getMessage();
			$error   = true;
		}

		echo new JsonResponse($result, $message, $error);

		Factory::getApplication()->close();
	}
}
