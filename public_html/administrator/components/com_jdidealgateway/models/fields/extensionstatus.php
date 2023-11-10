<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;

defined('_JEXEC') or die;

JLoader::registerAlias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField', '6.0');
FormHelper::loadFieldClass('list');

/**
 * Extension Order statuses.
 *
 * @package  JDiDEAL
 * @since    4.9.0
 */
class JdidealFormFieldExtensionstatus extends JFormFieldList
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.9.0
	 */
	protected $type = 'Extensionstatus';

	/**
	 * Build a list of available order statuses.
	 *
	 * @return  array  Order options
	 *
	 * @since   4.9.0
	 */
	public function getOptions(): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					[
						'extension',
						'name',
                    ],
					[
						'value',
						'text'
                    ]
				)
			)
			->from($db->quoteName('#__jdidealgateway_statuses'))
			->order($db->quoteName('name'));
		$db->setQuery($query);

		$options = $db->loadObjectList();

		return array_merge(parent::getOptions(), $options);
	}
}
