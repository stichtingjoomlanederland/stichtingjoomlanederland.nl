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

namespace Ropayments\Rsformpro\Layouts\Responsive;

defined('_JEXEC') or die;

use Ropayments\Rsformpro\Layouts\QuantityBoxTrait;
use Ropayments\Rsformpro\Layouts\RadioGroupTrait;
use RSFormProFieldResponsiveRadioGroup;

/**
 * RSForms Pro Checkbox group for RO Payments.
 *
 * @package  JDiDEAL
 * @since    6.6.0
 */
class RadioGroup extends RSFormProFieldResponsiveRadioGroup
{
	use RadioGroupTrait, QuantityBoxTrait;

	/**
	 * Function to build input field
	 *
	 * @param   array  $data  Array data from the form
	 *
	 * @return  string
	 *
	 * @since   6.6.0
	 */
	protected function buildInput($data): string
	{
		return $this->buildInputField($data);
	}
}
