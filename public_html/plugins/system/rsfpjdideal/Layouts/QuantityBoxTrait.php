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

use InvalidArgumentException;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * A fields helper to render UI elements.
 *
 * @package  JDiDEAL
 * @since    6.6.0
 */
trait QuantityBoxTrait
{
	/**
	 * Render the quantity box.
	 *
	 * @param   array  $args     An array with form details.
	 * @param   int    $counter  A counter to ensure unique IDs.
	 *
	 * @return  string  The quantity box markup.
	 *
	 * @since   6.6.0
	 *
	 * @throws  InvalidArgumentException
	 */
	public function renderQuantityBox(array $args, int $counter = 0): string
	{
		$output = '';

		if (!array_key_exists('BOXTYPE', $args))
		{
			return $output;
		}

		$randomId = $this->attr['data-ropayments'] ?? '';

		// Check the counter to make sure the input field is unique
		$idCounter = '';

		if ($counter > 0)
		{
			// Arrays start with 0, so we need to make sure we account for this offset
			$counter--;

			$idCounter = '-' . $counter;
		}

		// Check for the default value, otherwise set it
		$defaultValue = '' !== $args['DEFAULTQUANTITY']
			? $args['DEFAULTQUANTITY'] : 1;

		// Get the selected quantity value
		if (isset($this->quantity)
			&& is_array($this->quantity)
			&& 0 !== count($this->quantity)
			&& array_key_exists(
				$counter, $this->quantity
			))
		{
			$defaultValue
				= $this->quantity[$counter];
		}

		switch ($args['BOXTYPE'])
		{
			default:
			case 'INPUT':
				$output = '<input type="text" 
								name="form[ropayments][quantity][' . $args['NAME'] . '][]"
								id="jdideal-quantity-'
					. $args['componentId'] . $idCounter . '"
								value="' . $defaultValue . '"
								class="jdideal-quantityBox"
								data-ropayments="' . $randomId . '" >' . "\r\n";
				break;
			case 'DROPDOWN':
				$items      = array();
				$boxMinimum = array_key_exists('BOXMIN', $args)
					? (int) $args['BOXMIN'] : 1;
				$boxMaximum = array_key_exists('BOXMAX', $args)
					? (int) $args['BOXMAX'] : 10;
				$boxStep    = array_key_exists('BOXSTEP', $args) && $args['BOXSTEP'] !== ''
					? (int) $args['BOXSTEP'] : 1;

				for ($i = $boxMinimum; $i <= $boxMaximum; $i++)
				{
					if ($boxStep === 1 || ($i % $boxStep === 1))
					{
						$items[] = HTMLHelper::_('select.option', $i, $i);
					}
				}

				$output = HTMLHelper::_(
					'select.genericlist',
					$items,
					'form[ropayments][quantity][' . $args['NAME'] . '][]',
					'data-ropayments="' . $randomId
					. '" class="jdideal-quantityBox"',
					'value',
					'text',
					$defaultValue,
					'jdideal-quantity-' . $args['componentId'] . $idCounter
				);
				break;
			case 'NUMBER':
				$boxMinimum = array_key_exists('BOXMIN', $args)
					? (int) $args['BOXMIN'] : 1;
				$boxMaximum = array_key_exists('BOXMAX', $args)
					? (int) $args['BOXMAX'] : 10;
				$boxStep    = array_key_exists('BOXSTEP', $args)
					? (int) $args['BOXSTEP'] : 1;

				$output = '<input type="number" name="form[ropayments][quantity]['
					. $args['NAME'] . '][]"
							id="jdideal-quantity-' . $args['componentId']
					. $idCounter . '" min="' . $boxMinimum . '" max="'
					. $boxMaximum . '" step="' . $boxStep . '" value="'
					. $defaultValue . '"
							class="jdideal-quantityBox"
							data-ropayments="' . $randomId . '" >' . "\r\n";
				break;
		}

		return $output;
	}
}
