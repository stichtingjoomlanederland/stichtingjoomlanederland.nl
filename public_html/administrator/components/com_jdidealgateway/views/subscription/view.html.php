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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

/**
 * Subscription view.
 *
 * @package  JDiDEAL
 * @since    6.4.0
 */
class JdidealgatewayViewSubscription extends HtmlView
{
	/**
	 * RO Payments helper
	 *
	 * @var    JdIdealgatewayHelper
	 * @since  4.9.0
	 */
	protected $jdidealgatewayHelper;

	/**
	 * Form with settings
	 *
	 * @var    Form
	 * @since  4.9.0
	 */
	protected $form;

	/**
	 * The item object
	 *
	 * @var    object
	 * @since  4.9.0
	 */
	protected $item;

	/**
	 * Get the state
	 *
	 * @var    Registry
	 * @since  4.9.0
	 */
	protected $state;

	/**
	 * Access rights of a user
	 *
	 * @var    CMSObject
	 * @since  4.9.0
	 */
	protected $canDo;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @throws  Exception
	 *
	 * @since   4.9.0
	 */
	public function display($tpl = null)
	{
		/** @var JdidealgatewayModelSubscription $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();
		$this->canDo = ContentHelper::getActions('com_jdidealgateway');

		$this->addToolbar();

		return parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   6.4.0
	 */
	private function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		ToolbarHelper::title(Text::_('COM_ROPAYMENTS_SUBSCRIPTION_EDIT'), 'checkin');

		if ($this->canDo->get('core.edit') || $this->canDo->get('core.create'))
		{
			ToolbarHelper::apply('subscription.apply');
			ToolbarHelper::save('subscription.save');
		}

		if (0 === $this->item->id)
		{
			ToolbarHelper::cancel('subscription.cancel');
		}
		else
		{
			ToolbarHelper::cancel('subscription.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
