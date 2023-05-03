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

use RSFormProFieldMultiple;
use RSFormProHelper;

defined('_JEXEC') or die;

/**
 * Render a multiple products field.
 *
 * @package  JDiDEAL
 * @since    6.6.0
 */
class MultipleProducts extends RSFormProFieldMultiple
{
	/**
	 * List of all the products on the form
	 *
	 * @var    array
	 * @since  6.6.0
	 */
	private $products = [];

	/**
	 * Get the preview of field
	 *
	 * @return  string
	 *
	 * @since   6.5.0
	 */
	public function getPreviewInput(): string
	{
		$out = '<span class="rsficon jdicon-jdideal" style="font-size:24px;margin-right:5px"></span> ';

		return $out . $this->getFormInput();
	}

	/**
	 * Get form input
	 *
	 * [c] Checked item
	 * [d] Disabled item
	 * [g] Optgroup
	 *
	 * @see     https://www.rsjoomla.com/support/documentation/rsform-pro/form-fields/checkbox-group.html
	 *
	 * @return  string
	 *
	 * @since   6.5.0
	 */
	public function getFormInput(): string
	{
		$out = '';

		if ($items = $this->getItems())
		{
			$this->data['ITEMS'] = [];

			$special = ['[c]', '[g]', '[/g]', '[d]'];

			foreach ($items as $ikey => $item)
			{
				$strippedItem = str_replace($special, '', $item);
				$hasGroup     = strpos($item, '[g]') !== false || strpos($item, '[/g]') !== false;
				$text         = $item;

				if (!$hasGroup)
				{
					$value = $text = $strippedItem;

					if (strpos($strippedItem, '|') !== false)
					{
						[$value, $text] = explode('|', $strippedItem, 2);

						$value = trim($value);
					}

					// Field identifier
					$fieldIdentifier = strlen($value) > 0 ? $text : '';

					// Show the price
					// Check if we have a price higher than 0
					if (!isset($this->data['HIDE_PRICE']))
					{
						$this->data['HIDE_PRICE'] = 'NO';
					}

					if (strtoupper($this->data['HIDE_PRICE']) === 'NO')
					{
						$text .= $value > 0
							? ' - ' . $this->data['CURRENCY'] . number_format(
								(float) $value,
								$this->settings->get('numberDecimals', 2),
								$this->settings->get('decimalSeparator', ','),
								$this->settings->get('thousandSeparator', '.')
							)
							: '';
					}

					// Check if we need to hide the description
					if (isset($this->data['HIDE_DESCRIPTION'])
						&& strtoupper($this->data['HIDE_DESCRIPTION']) === 'YES'
					)
					{
						if (strtoupper($this->data['HIDE_PRICE']) === 'YES')
						{
							continue;
						}

						$text = $this->data['CURRENCY']
							. number_format(
								(float) $value,
								$this->settings->get('numberDecimals', 2),
								$this->settings->get('decimalSeparator', ','),
								$this->settings->get('thousandSeparator', '.')
							);
					}

					$product        = [$this->data['NAME'] . $ikey . '|_|' . $fieldIdentifier => $value];
					$text           = $fieldIdentifier . '|' . $text;
					$this->products = $this->merge($this->products, $product);

					foreach ($special as $flag)
					{
						if (strpos($item, $flag) !== false)
						{
							$text .= $flag;
						}
					}
				}

				$this->data['ITEMS'][] = $text;
			}

			$this->data['ITEMS'] = implode("\n", $this->data['ITEMS']);
		}

		$form       = RSFormProHelper::getForm($this->formId);
		$layoutName = (string) preg_replace('/[^A-Z0-9]/i', '', $form->FormLayoutName);

		$config = [
			'formId'          => $this->formId,
			'layout'          => $layoutName,
			'componentId'     => $this->componentId,
			'data'            => $this->data,
			'value'           => $this->value,
			'quantity'        => $this->value[$this->data['NAME']]['quantity'] ?? 0,
			'invalid'         => $this->invalid,
			'errorClass'      => $this->errorClass,
			'fieldErrorClass' => $this->fieldErrorClass,
			'preview'         => $this->preview,
			'items'           => $items,
		];

		switch ($this->getProperty('VIEW_TYPE'))
		{
			case 'DROPDOWN':
				$out .= $this->getSelectInput($config);
				break;

			case 'RADIOGROUP':
				$out .= $this->getRadioInput($config);
				break;

			case 'CHECKBOX':
				$out .= $this->getCheckboxInput($config);
				break;
		}

		return $out;
	}

	/**
	 * Get selected input
	 *
	 * @param   array  $config  The config of selected input
	 *
	 * @return  string
	 *
	 * @since   6.5.0
	 */
	protected function getSelectInput(array $config): string
	{
		$field = new Selectlist($config);

		$field->setId('jdideal-' . $this->componentId);

		return $field->output;
	}

	/**
	 * Get radio input
	 *
	 * @param   array  $config  The config of radio input
	 *
	 * @return  string
	 *
	 * @since   6.5.0
	 */
	protected function getRadioInput(array $config): string
	{
		$className = '\Ropayments\Rsformpro\Layouts\\' . ucfirst($config['layout']) . '\RadioGroup';

		if ($config['preview'])
		{
			$className = '\Ropayments\Rsformpro\Layouts\RadioGroup';
		}

		$field = new $className($config);

		$field->setId('jdideal-' . $this->componentId);

		return $field->output;
	}

	/**
	 * Get checkbox input
	 *
	 * @param   array  $config  The config of radio input
	 *
	 * @return  string
	 *
	 * @since   6.5.0
	 */
	protected function getCheckboxInput(array $config): string
	{
		$className = '\Ropayments\Rsformpro\Layouts\\' . ucfirst($config['layout']) . '\CheckboxGroup';

		if ($config['preview'])
		{
			$className = '\Ropayments\Rsformpro\Layouts\Checkboxgroup';
		}

		$field = new $className($config);

		$field->setId('jdideal-' . $this->componentId);

		return $field->output;
	}

	/**
	 * Get the generated products.
	 *
	 * @return  array  List of products.
	 *
	 * @since   6.6.0
	 */
	public function getProducts(): array
	{
		return $this->products;
	}

	/**
	 * Array merge based on key name.
	 *
	 * @param   array  $a  The main array.
	 * @param   array  $b  The array to merge.
	 *
	 * @return  array  Return the merged array
	 *
	 * @since   6.6.0
	 */
	private function merge(array $a, array $b): array
	{
		foreach ($b as $key => $value)
		{
			$a[$key] = $value;
		}

		return $a;
	}
}
