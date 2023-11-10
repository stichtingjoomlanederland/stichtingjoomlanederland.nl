<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Payment page controller.
 *
 * @package  JDiDEAL
 * @since    3.0
 */
class JdidealgatewayControllerPay extends BaseController
{
	/**
	 * Show the iDEAL payment page.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 * @throws  Exception
	 */
	public function sendmoney(): void
	{
		/** @var JdidealgatewayModelPay $payModel */
		$payModel = $this->getModel('pay', 'JdidealgatewayModel');

		/** @var JdidealgatewayViewPay $view */
		$view = $this->getView('pay', 'html');
		$view->setLayout('ideal');
		$view->set('task', $this->input->get('task'));
		$view->setModel($payModel, true);

		$data = $this->input->get('jform', [], 'array');
		$form = $payModel->getForm($data, false);
        $silent = $this->input->getBool('silent', false);

		if ($silent || !Factory::getUser()->guest)
		{
			$form->removeField('captcha');
		}

		$result = $form->validate($data);
		$app    = Factory::getApplication();

		if ($result)
		{
			$app->setUserState('com_jdidealgateway.pay.data', []);
		}

		if (!$result)
		{
			$app->setUserState('com_jdidealgateway.pay.data', $data);
			$app->enqueueMessage($payModel->getError(), 'error');
			$view->set('task', 'default');
			$view->setLayout('default');
		}

		$view->display();
	}

	/**
	 * Check the payment result.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 * @throws  Exception
	 */
	public function result(): void
	{
		/** @var JdidealgatewayViewPay $view */
		$view = $this->getView('pay', 'html');

		/** @var JdidealgatewayModelPay $payModel */
		$payModel = $this->getModel('pay', 'JdidealgatewayModel');

		$trans  = $this->input->getString('transactionId');
		$column = 'trans';

		if (empty($trans))
		{
			$trans  = $this->input->getString('pid');
			$column = 'pid';
		}

		$result = '';

		if ($trans)
		{
			$result = $payModel->getResult($trans, $column);
		}

		$view->setModel($this->getModel('Pay'), true);
		$view->set('task', $this->input->get('task'));
		$view->set('result', $result);
		$view->setLayout('result');

		$view->display();
	}
}
