<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Stripe\StripeClient;

defined('_JEXEC') or die;

JLoader::registerAlias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField', '6.0');
FormHelper::loadFieldClass('list');

/**
 * List of available banks.
 *
 * @package  JDiDEAL
 * @since    2.0.0
 */
class RopaymentsFormFieldPaymentmethods extends JFormFieldList
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	protected $type = 'Paymentmethods';

	/**
	 * Build a list of available banks.
	 *
	 * @return  string  HTML select list with banks.
	 *
	 * @since   2.0.0
	 */
	public function getInput(): string
	{
		require_once JPATH_LIBRARIES . '/Jdideal/vendor/autoload.php';
		$stripe
			       = new StripeClient('sk_test_51KXhvkIsiNZT04AeOG8KU5Z2mO0XKMLtcgED4sL8P9Oa8Wot1sSQHcVcTxgrZxOCNbLzo5vPcMkOBRuHqeXNGgcS00gU7DYeWG');
		$customers = $stripe->customers->all();

		?>
		<pre><?php
		echo __FILE__ . '::' . __LINE__ . ':: ';
		echo 'customers: ';
		echo '<div style="font-size: 1.5em;">';
		print_r($customers);
		echo '</div>';
		?></pre><?php
		exit();

		$methods = $stripe->customers->allPaymentMethods('cus_LEBXu4wfjKJBuG', ['type' => 'card']);

		?>
		<pre><?php
		echo __FILE__ . '::' . __LINE__ . ':: ';
		echo 'methods: ';
		echo '<div style="font-size: 1.5em;">';
		print_r($methods);
		echo '</div>';
		?></pre><?php

		exit();
		// Create the list of banks
		$options   = array();
		$options[] = HTMLHelper::_('select.option', 'RABOBANKSEPATEST', 'Rabobank | TEST Server');

		return HTMLHelper::_('select.genericlist', $options, $this->name, null, 'value', 'text', $this->value,
			$this->id);
	}
}
