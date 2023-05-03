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

namespace Ropayments\Rsformpro\Layouts;

use Joomla\Registry\Registry;
use Ropayments\Rsformpro\Price;
use RSFormProField;

defined('_JEXEC') or die;

/**
 * Render a single product field.
 *
 * @package  JDiDEAL
 * @since    7.0.0
 *
 * @property-read  Registry $settings The form settings
 */
class Singleproduct extends RSFormProField
{
	/**
	 * The price functions
	 *
	 * @var Price
	 * @since  7.0.0
	 */
	private $price;

	/**
	 * Class constructor.
	 *
	 * @since  7.0.0
	 */
	public function __construct($config)
	{
		parent::__construct($config);

		$this->price = new Price($this->settings);
	}

	/**
	 * Get the preview of field
	 *
	 * @return  string
	 *
	 * @since   7.0.0
	 */
	public function getPreviewInput()
	{
		$caption  = $this->getProperty('CAPTION');
		$price    = $this->getProperty('PRICE');
		$currency = $this->getProperty('CURRENCY');

		return '<span class="rsficon jdicon-jdideal" style="font-size:24px;margin-right:5px"></span> '
			. $this->price->getPriceMask($caption, $price, $currency);
	}

	public function getFormInput()
	{
		$caption    = $this->getProperty('CAPTION');
		$price      = $this->getProperty('PRICE');
		$currency   = $this->getProperty('CURRENCY');
		$attributes = $this->getProperty('ADDITIONALATTRIBUTES');

		$out = '<input type="hidden"' . $attributes . ' data-ropayments-field="single" value="' . $this->escape($price)
			. '" />';
		$out .= '<input type="hidden" name="' . $this->getName() . '" id="' . $this->getId() . '" value="'
			. $this->escape($caption) . '" />';

		if ($this->getProperty('SHOW'))
		{
			$out .= $this->price->getPriceMask($caption, $price, $currency);
		}

		return $out;
	}
}
