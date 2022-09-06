<?php
/**
 * @package     JDiDEAL
 * @subpackage  RSForm!Pro
 *
 * @author      Roland Dalmulder <contact@rolandd.com>
 * @copyright   Copyright (C) 2009 - 2022 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Ropayments\Rsformpro\Layouts;

defined('_JEXEC') or die;

use RSFormProFieldSelectlist;

/**
 * RSForms Pro select list for RO Payments.
 *
 * @package  JDiDEAL
 * @since    7.0.0
 */
class Selectlist extends RSFormProFieldSelectlist
{
	use QuantityBoxTrait;

	/**
	 * Render the preview of the field.
	 *
	 * @return  string  The HTML output.
	 *
	 * @since   7.0.0
	 */
	public function getPreviewInput(): string
	{
		return $this->getQuantityBox() . parent::getPreviewInput();
	}

	/**
	 * Function to build input field
	 *
	 * @return  string
	 *
	 * @since   7.0.0
	 */
	public function getFormInput(): string
	{
		return $this->getQuantityBox() . parent::getFormInput();
	}

	/**
	 * Render the quantity box if set.
	 *
	 * @return  string  The rendered quantity box.
	 *
	 * @since   7.0.0
	 */
	private function getQuantityBox(): string
	{
		$quantityBox = '';

		$this->getAttributes();

		if (array_key_exists('QUANTITYBOX', $this->data)
			&& $this->data['QUANTITYBOX'] === 'YES'
			&& $this->data['MULTIPLE'] === 'NO')
		{
			$quantityBox = $this->renderQuantityBox(
				$this->data, 1
			);
		}

		return $quantityBox;
	}
}
