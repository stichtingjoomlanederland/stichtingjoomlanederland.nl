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

use RSFormProFieldItem;

/**
 * A trait to render a checkbox field.
 *
 * @package  JDiDEAL
 * @since    6.6.0
 */
trait CheckboxTrait
{
	use QuantityBoxTrait;

	/**
	 * Render the input field for a checkbox.
	 *
	 * @param   array  $data  The data to use for rendering
	 *
	 * @return  string  The rendered input field.
	 *
	 * @since   6.6.0
	 */
	public function buildInputField($data): string
	{
		/**
		 * @var string             $name
		 * @var string             $id
		 * @var string             $additional
		 * @var array              $prices
		 * @var string             $flow
		 * @var string             $value
		 * @var int                $i
		 * @var RSFormProFieldItem $item
		 */
		extract($data);

		if (!isset($name))
		{
			$name = mt_rand();
		}

		$html = '';

		if (array_key_exists('QUANTITYBOX', $this->data)
			&& $this->data['QUANTITYBOX'] === 'YES'
			&& $this->data['MULTIPLE'] === 'NO')
		{
			$html .= $this->renderQuantityBox(
				$this->data, $data['i'] + 1
			);
		}

		$attributes = $this->getAttributes();

		if (!array_key_exists('class', $attributes))
		{
			$attributes['class'] = '';
		}

		if (array_key_exists('CHECKBOX_INVISIBLE', $this->data)
			&& $this->data['CHECKBOX_INVISIBLE'] === 'YES')
		{
			$attributes['class'] .= ' rsfpjdhidden';
		}

		$html .= '<input type="checkbox" ';

		if ($item->flags['disabled'])
		{
			$html .= ' disabled="disabled"';
		}

		if ($item->value === $value
			|| (isset($this->data['CHECKBOX_CHECKED'])
				&& $this->data['CHECKBOX_CHECKED'] === 'YES'))
		{
			$html .= ' checked="checked"';
		}

		if (substr($name, -2) !== '[]')
		{
			$name .= '[' . $data['i'] . ']';
		}

		if (substr($name, -2) === '[]')
		{
			$name = substr($name, 0, -2) . '[' . $data['i'] . ']';
		}

		$html .= ' name="' . $name . '"';
		$html .= ' value="' . $this->escape($item->value) . '"';
		$html .= ' id="jdideal-' . $this->data['componentId'] . '-' . $i . '"';
		$html .= ' class="' . $attributes['class'] . '"';

		if (!empty($additional))
		{
			$html .= $additional;
		}

		$html .= ' />';

		return $html;
	}
}
