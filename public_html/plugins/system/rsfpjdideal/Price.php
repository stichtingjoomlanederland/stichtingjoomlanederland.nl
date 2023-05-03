<?php
/**
 * @package     JDiDEAL
 * @subpackage  RSForm!Pro
 *
 * @author      Roland Dalmulder <contact@rolandd.com>
 * @copyright   Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Ropayments\Rsformpro;

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Get the different price masks
 *
 * @package  JDiDEAL
 * @since    7.0.0
 */
class Price
{
	/**
	 * The form settings
	 *
	 * @var     Settings
	 * @since   7.0.0
	 */
	private $settings;

	/**
	 * The class constructor.
	 *
	 * @since  7.0.0
	 */
	public function __construct(Registry $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Format the price.
	 *
	 * @param   string  $productName  The product name
	 * @param   string  $price        The price value
	 * @param   string  $currency     The currency used for this value
	 *
	 * @return  string  The formatted price.
	 *
	 * @since   7.0.0
	 */
	public function getPriceMask(string $productName, string $price, string $currency): string
	{
		$numberDecimals    = $this->settings->get('numberDecimals', 2);
		$decimalSeparator  = $this->settings->get('decimalSeparator', ',');
		$thousandSeparator = $this->settings->get('thousandSeparator', '.');
		$priceMask         = $this->settings->get('priceMask', '');

		$formattedPrice = number_format((float) $price, $numberDecimals, $decimalSeparator, $thousandSeparator);
		$replacements   = [
			'{product}'  => $productName,
			'{price}'    => $formattedPrice,
			'{currency}' => $currency,
		];

		return str_replace(array_keys($replacements), array_values($replacements), $priceMask);
	}

	/**
	 * Format the price.
	 *
	 * @param   string  $price     The price value
	 * @param   string  $currency  The currency used for this value
	 *
	 * @return  string  The formatted price.
	 *
	 * @since   7.0.0
	 */
	public function getAmountMask(string $price, string $currency): string
	{
		$numberDecimals    = $this->settings->get('numberDecimals', 2);
		$decimalSeparator  = $this->settings->get('decimalSeparator', ',');
		$thousandSeparator = $this->settings->get('thousandSeparator', '.');
		$amountMask        = $this->settings->get('amountMask', '');

		$formattedPrice = number_format((float) $price, $numberDecimals, $decimalSeparator, $thousandSeparator);
		$replacements   = [
			'{price}'    => $formattedPrice,
			'{currency}' => $currency,
		];

		return str_replace(array_keys($replacements), array_values($replacements), $amountMask);
	}

	/**
	 * Get the total price mask.
	 *
	 * @param   string  $totalPrice    The total price
	 * @param   string  $currency      The currency used for this value
	 * @param   int     $formId        The form ID
	 * @param   int     $submissionId  The submission ID
	 *
	 * @return  string  The formatted price.
	 *
	 * @since   7.0.0
	 */
	public function getTotalMask(string $totalPrice, string $currency, int $formId, int $submissionId): string
	{
		$numberDecimals    = $this->settings->get('numberDecimals', 2);
		$decimalSeparator  = $this->settings->get('decimalSeparator', ',');
		$thousandSeparator = $this->settings->get('thousandSeparator', '.');
		$taxType           = (int) $this->settings->get('taxType', 0);
		$taxValue          = (int) $this->settings->get('taxValue');
		$priceMask         = $this->settings->get('totalMask', '');
		$taxAmount         = 0;
		$formattedPrice    = number_format((float) $totalPrice, $numberDecimals, $decimalSeparator, $thousandSeparator);

		if ($taxValue)
		{
			$taxAmount = $taxValue;

			if ($taxType === 0)
			{
				$taxAmount = $totalPrice / (100 + $taxValue) * $taxValue;
			}
		}

		$tax = number_format((float) $taxAmount, $numberDecimals, $decimalSeparator, $thousandSeparator);

		$replacements = [
			'{price}'    => $formattedPrice,
			'{currency}' => $currency,
			'{tax}'      => $tax,
		];

		return str_replace(array_keys($replacements), array_values($replacements), $priceMask);
	}
}
