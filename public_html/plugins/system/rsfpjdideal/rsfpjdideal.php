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

defined('_JEXEC') or die;

use Jdideal\Gateway;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;
use Ropayments\Rsformpro\Layouts\Discount;
use Ropayments\Rsformpro\Layouts\MultipleProducts;
use Ropayments\Rsformpro\Layouts\QuantityBoxTrait;
use Ropayments\Rsformpro\Layouts\Singleproduct;
use Ropayments\Rsformpro\Layouts\Textbox;
use Ropayments\Rsformpro\Price;
use Ropayments\Rsformpro\Settings;

JLoader::registerNamespace('Ropayments\Rsformpro', __DIR__, false, false, 'psr4');

const ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT    = 5575;
const ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS = 5576;
const ROPAYMENTS_FIELD_PAYMENT_INPUT             = 5578;
const ROPAYMENTS_FIELD_PAYMENT_TOTAL             = 5577;
const ROPAYMENTS_FIELD_PAYMENT_CHOOSE            = 5579;
const ROPAYMENTS_FIELD_PAYMENT_DISCOUNT          = 5580;

/**
 * RSForm! Pro RO Payments plugin.
 *
 * @package     JDiDEAL
 * @subpackage  RSForm!Pro
 * @since       1.0.0
 */
class PlgSystemRsfpJdideal extends CMSPlugin
{
	use QuantityBoxTrait;

	/**
	 * Database driver
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0.0
	 */
	protected $db;

	/**
	 * An application instance
	 *
	 * @var    SiteApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The component ID for the payment package field
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	private $componentId = ROPAYMENTS_FIELD_PAYMENT_CHOOSE;

	/**
	 * List of all the products on the form
	 *
	 * @var    array
	 * @since  6.0.0
	 */
	private $products = [];

	/**
	 * List of RO Payments components
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private $ropaymentsFields;

	/**
	 * A random number for each form to handle the JS calls.
	 *
	 * @var    string
	 * @since  4.2.0
	 */
	private $randomId = '';

	/**
	 * Set if the script has been loaded in an article
	 *
	 * @var    boolean
	 * @since  4.6.1
	 */
	private $setScript = false;

	/**
	 * Constructor.
	 *
	 * 5575: Single Product
	 * 5576: Multiple Products
	 * 5577: Total
	 * 5578: Input field
	 * 5579: iDEAL option for payment package
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if (!$this->canRun())
		{
			return;
		}

		$this->ropaymentsFields = [
			ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT,
			ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS,
			ROPAYMENTS_FIELD_PAYMENT_INPUT,
			ROPAYMENTS_FIELD_PAYMENT_TOTAL,
			ROPAYMENTS_FIELD_PAYMENT_DISCOUNT,
		];

		$this->initAutoLoader();

		$lang = Factory::getLanguage();
		$lang->load('plg_system_rsfpjdideal');
	}

	/**
	 * Check if RSFormPro is loaded.
	 *
	 * @return  boolean  The field option objects.
	 *
	 * @since   2.2.0
	 */
	public function canRun(): bool
	{
		if (!file_exists(JPATH_LIBRARIES . '/Jdideal'))
		{
			return false;
		}

		if (class_exists('RSFormProHelper'))
		{
			return true;
		}

		$helper = JPATH_ADMINISTRATOR
			. '/components/com_rsform/helpers/rsform.php';

		if (file_exists($helper))
		{
			require_once $helper;
			RSFormProHelper::readConfig(true);

			return true;
		}

		return false;
	}

	/**
	 * Setup the autoloader for the dependencies.
	 *
	 * @return  void
	 *
	 * @since   6.6.0
	 */
	private function initAutoLoader()
	{
		JLoader::registerNamespace('Jdideal', JPATH_LIBRARIES);

		if (class_exists(Gateway::class) === false)
		{
			JLoader::registerNamespace('Jdideal', JPATH_LIBRARIES . '/Jdideal');
		}

		$classes = [
			'RSFormProHelper'             => JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php',
			'RSFormProPaymentHelper'      => JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/payment.php',
			'RSFormProField'              => JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/field.php',
			'RSFormProFieldMultiple'      => JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fieldmultiple.php',
			'RSFormProFieldCheckboxGroup' => JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/fields/checkboxgroup.php',
			'RSFormProFieldRadioGroup'    => JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/fields/radiogroup.php',
			'RSFormProFieldSelectList'    => JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/fields/selectlist.php',
			'RSFormProFieldTextbox'       => JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/fields/textbox.php',
			'RSFormProFieldDiscount'      => JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/fields/discount.php',
			'RsfpjdidealMultipleProducts' => __DIR__ . '/helpers/RsfpjdidealMultipleProducts.php',
		];

		$layouts = [
			'Bootstrap2',
			'Bootstrap3',
			'Bootstrap4',
			'Bootstrap5',
			'Foundation',
			'Responsive',
			'Uikit',
			'Uikit3',
		];

		foreach ($layouts as $layout)
		{
			$layout                                                = strtolower($layout);
			$classes['RSFormProField' . $layout . 'CheckboxGroup'] = JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/fields/' . $layout . '/checkboxgroup.php';

			$classes['RSFormProField' . $layout . 'RadioGroup'] = JPATH_ADMINISTRATOR
				. '/components/com_rsform/helpers/fields/' . $layout . '/radiogroup.php';
		}

		foreach ($classes as $class => $path)
		{
			JLoader::register($class, $path);
		}
	}

	/**
	 * Initialise the plugin.
	 *
	 * @return  void
	 *
	 * @since   2.12.0
	 *
	 * @throws  RuntimeException
	 */
	public function onRsformBackendInit(): void
	{
		if (!$this->canRun())
		{
			return;
		}

		// Cron that sets non paid subscribers to denied after 12 h
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__rsform_submission_values', 'sv'))
			->leftJoin(
				$this->db->quoteName('#__rsform_submissions', 's') . ' ON '
				. $this->db->quoteName('s.SubmissionId') . ' = '
				. $this->db->quoteName('sv.SubmissionId')
			)
			->set($this->db->quoteName('sv.FieldValue') . ' = -1')
			->where(
				$this->db->quoteName('sv.FieldName') . ' = ' . $this->db->quote(
					'_STATUS'
				)
			)
			->where($this->db->quoteName('sv.FieldValue') . ' = 0')
			->where(
				$this->db->quoteName('s.DateSubmitted') . ' < '
				. $this->db->quote(
					date('Y-m-d H:i:s', strtotime('-' . $this->params->get('expireHours') . ' hours'))
				)
			);
		$this->db->setQuery($query)->execute();
	}

	public function rsfp_bk_onInit(): void
	{
		$this->onRsformBackendInit();
	}

	/**
	 * Show the list of field options.
	 *
	 * @return  void
	 *
	 * @since   2.12.0
	 */
	public function onRsformBackendAfterShowComponents(): void
	{
		// Load the RO Payments stylesheet
		HTMLHelper::stylesheet(
			'com_jdidealgateway/jdidealgateway.css',
			['relative' => true, 'version' => 'auto']
		);

		?>
		<li class="rsform_navtitle"><?php
			echo Text::_('PLG_RSFP_JDIDEAL_LABEL'); ?></li>
		<li>
			<a href="javascript: void(0);"
			   onclick="displayTemplate('5575');return false;" id="rsfpc5575">
				<span class="rsficon jdicon-jdideal"></span>
				<span class="inner-text">
					<?php
					echo Text::_('PLG_RSFP_JDIDEAL_SPRODUCT'); ?>
				</span>
			</a>
		</li>
		<li>
			<a href="javascript: void(0);"
			   onclick="displayTemplate('5576');return false;" id="rsfpc5576">
				<span class="rsficon jdicon-jdideal"></span>
				<span class="inner-text">
					<?php
					echo Text::_('PLG_RSFP_JDIDEAL_MPRODUCT'); ?>
				</span>
			</a>
		</li>
		<li>
			<a href="javascript: void(0);"
			   onclick="displayTemplate('5578');return false;" id="rsfpc5578">
				<span class="rsficon jdicon-jdideal"></span>
				<span class="inner-text">
					<?php
					echo Text::_('PLG_RSFP_JDIDEAL_INPUTBOX'); ?>
				</span>
			</a>
		</li>
		<li>
			<a href="javascript: void(0);"
			   onclick="displayTemplate('5580');return false;" id="rsfpc5580">
				<span class="rsficon jdicon-jdideal"></span>
				<span class="inner-text">
					<?php
					echo Text::_('PLG_RSFP_JDIDEAL_DISCOUNT'); ?>
				</span>
			</a>
		</li>
		<li>
			<a href="javascript: void(0);"
			   onclick="displayTemplate('5577');return false;" id="rsfpc5577">
				<span class="rsficon jdicon-jdideal"></span>
				<span class="inner-text">
					<?php
					echo Text::_('PLG_RSFP_JDIDEAL_TOTAL'); ?>
				</span>
			</a>
		</li>
		<li>
			<a href="javascript: void(0);"
			   onclick="displayTemplate('5579');return false;" id="rsfpc5579">
				<span class="rsficon jdicon-jdideal"></span>
				<span class="inner-text">
					<?php
					echo Text::_('PLG_RSFP_JDIDEAL_BUTTON'); ?>
				</span>
			</a>
		</li>
		<?php
	}

	public function rsfp_bk_onAfterShowComponents(): void
	{
		$this->onRsformBackendAfterShowComponents();
	}

	/**
	 * Create the preview of the selected field.
	 *
	 * @param   array  $args  The field arguments and their values.
	 *
	 * @return  void
	 *
	 * @since   2.12.0
	 */
	public function onRsformBackendAfterCreateComponentPreview(array $args = []
	): void {
		if ($this->canRun() === false
			|| !in_array((int) $args['data']['componentTypeId'],
				array_merge($this->ropaymentsFields, [ROPAYMENTS_FIELD_PAYMENT_CHOOSE]))
		)
		{
			return;
		}

		$formId   = (int) $args['formId'];
		$settings = $this->loadFormSettings($formId);
		$style    = 'style="font-size:24px;margin-right:5px"';

		switch ($args['ComponentTypeName'])
		{
			case 'jdidealSingleProduct':
				$args['out'] = $this->renderSingleField($args, true);
				break;
			case 'jdidealMultipleProducts':
				$args['out'] = $this->renderMultipleFields($args, true);
				break;
			case 'jdidealTotal':
				$args['out'] = '<td>' . $args['data']['CAPTION'] . '</td>';
				$args['out'] .= '<td><span class="rsficon jdicon-jdideal" '
					. $style . '><span class="small">' . Text::_('PLG_RSFP_JDIDEAL_TOTAL') . '</span></span> '
					. number_format(
						0,
						$settings->get('numberDecimals', 2),
						$settings->get('decimalSeparator', ','),
						$settings->get('thousandSeparator', '.')
					) . ' ' . $settings->get('currency') . '</td>';
				break;
			case 'jdidealInputbox':
				$args['out'] = $this->renderInputField($args, true);
				break;
			case 'jdidealDiscount':
				$args['out'] = $this->renderDiscountField($args, true);
				break;
			case 'jdidealButton':
				$args['out'] = '<td>&nbsp;</td>';
				$args['out'] .= '<td><span class="rsficon jdicon-jdideal" ' . $style
					. '></span> '
					. $args['data']['LABEL']
					. '</td>';
				break;
		}
	}

	public function rsfp_bk_onAfterCreateComponentPreview(array $args = []
	): void {
		$this->onRsformBackendAfterCreateComponentPreview($args);
	}

	/**
	 * Load the form settings.
	 *
	 * @param   int  $formId  The form ID to get the settings for.
	 *
	 * @return  Registry  The form settings.
	 *
	 * @since   4.0.0
	 *
	 * @throws  RuntimeException
	 */
	private function loadFormSettings(int $formId): Registry
	{
		$settings = new Settings;

		return $settings->loadFormSettings($formId);
	}

	/**
	 * Generates the front-end form.
	 *
	 * @param   array  $args  The field arguments and their values.
	 *
	 * @return  void
	 *
	 * @since   2.2.0
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 */
	public function onRsformBackendAfterCreateFrontComponentBody(array $args): void
	{
		$formId   = (int) $args['formId'];
		$settings = $this->loadFormSettings($formId);
		$form     = RSFormProHelper::getForm($formId);

		if ($form && $form->LoadFormLayoutFramework)
		{
			// Load the RO Payments RSForms! Pro stylesheet
			HTMLHelper::_(
				'stylesheet',
				'plg_system_rsfpjdideal/rsfpjdideal.css',
				['version' => 'auto', 'relative' => true]
			);
		}

		// Create a random number for the JS call
		$randomId       = UserHelper::genRandomPassword();
		$session        = Factory::getSession();
		$this->randomId = $session->get(
			'randomId' . $formId, false, 'rsfpjdideal'
		);

		if (!$this->randomId)
		{
			$session->set('randomId' . $formId, $randomId, 'rsfpjdideal');
			$this->randomId = $randomId;
		}

		switch ($args['r']['ComponentTypeId'])
		{
			case ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT:
				$args['out'] .= $this->renderSingleField($args);
				break;

			// Render the multiple products field
			case ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS:
				// Check if there are any items
				if (strlen(trim($args['data']['ITEMS'])) === 0)
				{
					throw new InvalidArgumentException(
						Text::sprintf(
							'PLG_RSFP_JDIDEAL_NO_MULTIPLE_ITEMS',
							$args['data']['NAME']
						)
					);
				}

				switch ($args['data']['VIEW_TYPE'])
				{
					case 'DROPDOWN':
					case 'CHECKBOX':
					case 'RADIOGROUP':
						$args['out'] .= $this->renderMultipleFields($args);
						break;
				}
				break;

			// Render the total field
			case ROPAYMENTS_FIELD_PAYMENT_TOTAL:
				$args['out'] = '';

				// Check if the total field should be displayed
				if (isset($args['data']['SHOW'])
					&& $args['data']['SHOW'] === 'YES')
				{
					$args['out'] .= $args['data']['CURRENCY'] .
						'<span id="jdideal_total_' . $args['formId']
						. '" class="rsform_jdideal_total">'
						. number_format(
							0,
							$settings->get('numberDecimals', 2),
							$settings->get('decimalSeparator', ','),
							$settings->get('thousandSeparator', '.')
						)
						. '</span> ';
				}

				$args['out'] .= '<input type="hidden" id="'
					. $args['data']['NAME'] . '" class="' . $this->randomId
					. '" value="" name="form[' . $args['data']['NAME']
					. ']" />';
				break;
			case ROPAYMENTS_FIELD_PAYMENT_INPUT:
				$args['out'] .= $this->renderInputField($args);
				break;
			case ROPAYMENTS_FIELD_PAYMENT_DISCOUNT:
				$args['out'] .= $this->renderDiscountField($args);
				break;
		}
	}

	public function rsfp_bk_onAfterCreateFrontComponentBody(array $args): void
	{
		$this->onRsformBackendAfterCreateFrontComponentBody($args);
	}

	/**
	 * Render the single product field.
	 *
	 * @param   array  $args     The form arguments
	 * @param   bool   $preview  If the preview should be rendered
	 *
	 * @return  string The rendered HTML
	 *
	 * @since   7.0.0
	 */
	private function renderSingleField(array $args, bool $preview = false): string
	{
		$args['data']['ADDITIONALATTRIBUTES'] = $args['data']['ADDITIONALATTRIBUTES'] ?? '';
		$args['data']['ADDITIONALATTRIBUTES'] .= ' data-ropayments="' . $this->randomId . '"';

		$config = [
			'formId'      => $args['formId'],
			'componentId' => $args['componentId'],
			'data'        => $args['data'],
			'value'       => $preview ? [] : $args['value'],
			'preview'     => $preview,
			'invalid'     => $preview ? false : $args['invalid'],
			'errorClass'  => '',
			'settings'    => $this->loadFormSettings($args['formId']),
		];

		$field = new Singleproduct($config);

		return $field->output;
	}

	/**
	 * Render the input fields for multiple.
	 *
	 * @param   array  $args     An array with form details.
	 * @param   bool   $preview  If the preview should be rendered
	 *
	 * @return  string  The rendered form fields.
	 *
	 * @since   4.0.0
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 */
	private function renderMultipleFields(array $args, bool $preview = false): string
	{
		$args['data']['ADDITIONALATTRIBUTES'] = $args['data']['ADDITIONALATTRIBUTES'] ?? '';
		$args['data']['ADDITIONALATTRIBUTES'] .= ' data-ropayments="' . $this->randomId . '"';

		$config = [
			'formId'      => $args['formId'],
			'componentId' => $args['componentId'],
			'data'        => $args['data'],
			'preview'     => $preview,
			'value'       => $preview ? [] : $args['value'],
			'invalid'     => $preview ? false : $args['invalid'],
			'errorClass'  => '',
			'settings'    => $this->loadFormSettings($args['formId']),
		];

		$field = new MultipleProducts($config);
		$field->setId('rsfpjdideal-' . $this->componentId);

		$html           = $field->output;
		$this->products = $this->merge($this->products, $field->getProducts());

		return $html;
	}

	/**
	 * Render the input field.
	 *
	 * @param   array  $args     An array with form details.
	 * @param   bool   $preview  If the preview should be rendered
	 *
	 * @return  string  The rendered form fields.
	 *
	 * @since   4.0.0
	 */
	private function renderInputField(array $args, bool $preview = false): string
	{
		$args['data']['ADDITIONALATTRIBUTES'] = $args['data']['ADDITIONALATTRIBUTES'] ?? '';
		$args['data']['ADDITIONALATTRIBUTES'] .= ' data-ropayments="' . $this->randomId
			. '" data-ropayments-field="input"';

		$config = [
			'formId'      => $args['formId'],
			'componentId' => $args['componentId'],
			'data'        => $args['data'],
			'preview'     => $preview,
			'value'       => $preview ? [] : $args['value'],
			'invalid'     => $preview ? false : $args['invalid'],
			'errorClass'  => '',
			'settings'    => $this->loadFormSettings($args['formId']),
		];

		$field = new Textbox($config);
		$field->setProperty('INPUTTYPE', strtolower($args['data']['BOXTYPE'] ?? 'INPUT'));
		$field->setProperty('ATTRMIN', $args['data']['BOXMIN'] ?? 0);
		$field->setProperty('ATTRMAX', $args['data']['BOXMAX'] ?? '');
		$field->setProperty('ATTRSTEP', $args['data']['BOXSTEP'] ?? 1);

		return $field->output;
	}

	/**
	 * Render the input field.
	 *
	 * @param   array  $args     An array with form details.
	 * @param   bool   $preview  If the preview should be rendered
	 *
	 * @return  string  The rendered form fields.
	 *
	 * @since   4.0.0
	 */
	private function renderDiscountField(array $args, bool $preview = false): string
	{
		$args['data']['ADDITIONALATTRIBUTES'] = $args['data']['ADDITIONALATTRIBUTES'] ?? '';
		$args['data']['ADDITIONALATTRIBUTES'] .= ' data-ropayments="' . $this->randomId
			. '" data-ropayments-field="discount"';

		$config = [
			'formId'      => $args['formId'],
			'componentId' => $args['componentId'],
			'data'        => $args['data'],
			'preview'     => $preview,
			'value'       => $preview ? [] : $args['value'],
			'invalid'     => $preview ? false : $args['invalid'],
			'errorClass'  => '',
			'settings'    => $this->loadFormSettings($args['formId']),
			'randomId'    => $this->randomId,
		];

		$field = new Discount($config);

		return $field->output;
	}

	/**
	 * Generates the HTML for the fields to show on the front-end.
	 *
	 * @param   array  $args  The field arguments and their values.
	 *
	 * @return  void
	 *
	 * @since   2.2.0
	 *
	 * @throws  RuntimeException
	 */
	public function onRsformFrontendBeforeFormDisplay(array $args): void
	{
		$formId = (int) $args['formId'];

		if (!$this->hasRopaymentsFields($formId))
		{
			return;
		}

		$settings = $this->loadFormSettings($formId);

		// Get the session info
		$session        = Factory::getSession();
		$this->randomId = $session->get(
			'randomId' . $args['formId'], null, 'rsfpjdideal'
		);
		$session->clear('randomId' . $args['formId']);

		// Find the multiple product fields
		$multipleProducts = RSFormProHelper::componentExists(
			$args['formId'], ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS
		);

		// Find the input boxes
		$inputBoxes    = RSFormProHelper::componentExists(
			$args['formId'], ROPAYMENTS_FIELD_PAYMENT_INPUT
		);
		$singleProduct = RSFormProHelper::componentExists(
			$args['formId'], ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT
		);

		// Merge them
		$ideals = array_merge(
			$multipleProducts, $inputBoxes, $singleProduct
		);

		// Find the total field
		$totalFieldId   = RSFormProHelper::componentExists(
			$args['formId'], ROPAYMENTS_FIELD_PAYMENT_TOTAL
		);
		$totalDetails   = array();
		$totalFieldName = '';

		if (array_key_exists(0, $totalFieldId))
		{
			$totalDetails = RSFormProHelper::getComponentProperties(
				$totalFieldId[0]
			);
		}

		if (array_key_exists('NAME', $totalDetails))
		{
			$totalFieldName = $totalDetails['NAME'];
		}

		$properties = RSFormProHelper::getComponentProperties($ideals);

		if (!is_array($ideals))
		{
			return;
		}

		$args['formLayout'] .= "\r\n" . '<script type="text/javascript">' . "\r\n";
		$args['formLayout'] .= 'window.addEventListener(\'DOMContentLoaded\', () => {' . "\r\n";
		$args['formLayout'] .= 'const rsfpjsJdideal' . $this->randomId
			. ' = new rsfpJdideal(' . $args['formId'] . ');' . "\r\n";

		// Set the random ID
		$args['formLayout'] .= 'rsfpjsJdideal' . $this->randomId
			. '.setRandomId("' . $this->randomId . '");' . "\r\n";

		// Set the price formatting
		$args['formLayout'] .= 'rsfpjsJdideal' . $this->randomId
			. '.setDecimals('
			. $settings->get('numberDecimals', 2) . ', "'
			. $settings->get('decimalSeparator', ',') . '", "'
			. $settings->get('thousandSeparator', '.') . '");'
			. "\r\n";

		// Set the total field
		$args['formLayout'] .= 'rsfpjsJdideal' . $this->randomId
			. '.setTotalField("' . $totalFieldName . '");' . "\r\n";

		// Set the tax rate
		if ($settings->get('taxValue', 0) > 0)
		{
			$args['formLayout'] .= 'rsfpjsJdideal' . $this->randomId
				. '.setTax(' . $settings->get(
					'taxType',
					0
				) . ',' . $settings->get('taxValue', 0) . ');' . "\r\n";
		}

		if (is_array($this->products))
		{
			foreach ($this->products as $product => $price)
			{
				$product = addslashes($product);
				$product = str_replace('[c]', '', $product);
				$price   = '' !== $price ? $price : 0;

				if (!preg_match('/[a-zA-Z]/', $price))
				{
					$args['formLayout'] .= 'rsfpjsJdideal'
						. $this->randomId . '.addProduct("' . $product
						. '","' . $price . '");' . "\r\n";
				}
			}
		}

		foreach ($ideals as $componentId)
		{
			$details = $properties[$componentId];

			// Check for the multiple products field dropdown
			if (array_key_exists('VIEW_TYPE', $details))
			{
				// Add the ID to the list
				$args['formLayout'] .= 'rsfpjsJdideal' . $this->randomId
					. '.addComponent(' . $componentId . ', \''
					. $details['NAME'] . '\');' . "\r\n";
			}
		}

		$args['formLayout'] .= <<<JS
  	rsfpjsJdideal$this->randomId.calculatePrice();
	const elements = document.querySelectorAll('[data-ropayments]');
    for (let i = 0; i < elements.length; i++) {
      if (elements[i].dataset.ropayments === '$this->randomId') {
        
      elements[i].addEventListener('change', () => {
          rsfpjsJdideal$this->randomId.calculatePrice();
        })
      }
};
JS;
		// Close the script tag
		$args['formLayout'] .= '})';
		$args['formLayout'] .= '</script>' . "\r\n";
	}

	public function rsfp_f_onBeforeFormDisplay(array $args): void
	{
		$this->onRsformFrontendBeforeFormDisplay($args);
	}

	/**
	 * Check if the form has any RO Payments fields.
	 *
	 * @param   int  $formId  The ID of the form to check.
	 *
	 * @return  boolean  True if RO Payments fields are present | False if no fields are present.
	 *
	 * @since   4.2.0
	 * @throws  RuntimeException
	 */
	private function hasRopaymentsFields($formId): bool
	{
		static $cache = [];

		if (!isset($cache[$formId]))
		{
			$cache[$formId] = RSFormProHelper::componentExists(
				$formId, $this->ropaymentsFields
			);
		}

		return $cache[$formId] ? true : false;
	}

	/**
	 * Store a new submission.
	 *
	 * @param   array  $args  The field arguments and their values.
	 *
	 * @return  boolean  Always returns true
	 *
	 * @since   2.12.0
	 */
	public function onRsformFrontendBeforeStoreSubmissions(array $args): bool
	{
		if (!$this->canRun())
		{
			return true;
		}

		$formId = (int) $args['formId'];

		if (RSFormProHelper::componentExists(
			$formId, $this->ropaymentsFields
		))
		{
			$settings           = $this->loadFormSettings($formId);
			$includeNonSelected = (bool) $settings->get('includeNonSelected', false);
			$quantity           = $args['post']['ropayments']['quantity'] ?? [];
			unset($args['post']['ropayments']);

			// Loop through all the arrays to see if there is a nested quantity array
			foreach ($args as $area => $arg)
			{
				if (is_array($arg))
				{
					foreach ($arg as $fieldName => $values)
					{
						if (($fieldName === 'ropayments'
								&& array_key_exists('quantity', $values))
							|| !isset($quantity[$fieldName])
						)
						{
							continue;
						}

						$process     = $includeNonSelected;
						$componentId = $this->getComponentId($fieldName, $formId);
						$viewType    = $this->getPropertyValue($componentId, 'VIEW_TYPE');

						foreach ($quantity[$fieldName] as $selected)
						{
							if ((int) $selected > 0)
							{
								$process = true;
							}
						}

						if ($viewType === 'DROPDOWN')
						{
							$process = true;
						}

						if (!$process)
						{
							continue;
						}

						if (!is_array($values))
						{
							foreach ($quantity[$fieldName] as $amount)
							{
								if ((int) $amount > 0)
								{
									$args[$area][$fieldName] = $amount . ' ' . $values;
								}
							}
						}

						if (is_array($values))
						{
							foreach ($values as $index => $value)
							{
								$amount = (int) $quantity[$fieldName][$index];

								if ($includeNonSelected
									|| $amount > 0
									|| $viewType === 'DROPDOWN'
								)
								{
									$args[$area][$fieldName][$index] = $amount . ' ' . $value;
								}
								else
								{
									unset($args[$area][$fieldName][$index]);
								}
							}
						}
					}
				}
			}

			// Set the initial payment status for a submission
			$args['post']['_STATUS'] = '0';
		}

		return true;
	}

	public function rsfp_f_onBeforeStoreSubmissions(array $args): bool
	{
		return $this->onRsformFrontendBeforeStoreSubmissions($args);
	}

	/**
	 * Check if a user field has duplicate values, if so, we can't reliably find what the user chose.
	 *
	 * @param   array  $values  The array of values to check for duplicates.
	 *
	 * @return  boolean  True on duplicate fields | False if there are no duplicate fields.
	 *
	 * @since   4.3.2
	 */
	private function hasDuplicateValues(array $values): bool
	{
		$duplicate  = false;
		$foundPrice = '';

		foreach ($values as $value)
		{
			[$price, $name] = explode('|', $value);

			if ($price === $foundPrice)
			{
				$duplicate = true;
				break;
			}

			$foundPrice = $price;
		}

		return $duplicate;
	}

	/**
	 * Get the component ID.
	 *
	 * @param   string  $name    The name of the component
	 * @param   int     $formId  The ID of the form
	 *
	 * @return  integer|null  The return value or null if the query failed.
	 *
	 * @since   2.12
	 * @throws  RuntimeException
	 */
	public function getComponentId(string $name, int $formId): ?int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('properties.ComponentId'))
			->from($this->db->quoteName('#__rsform_properties', 'properties'))
			->leftJoin(
				$this->db->quoteName('#__rsform_components', 'components')
				. ' ON ' .
				$this->db->quoteName('properties.ComponentId') . ' = '
				. $this->db->quoteName('components.ComponentId')
			)
			->where(
				$this->db->quoteName('properties.PropertyValue') . ' = '
				. $this->db->quote($name)
			)
			->where(
				$this->db->quoteName('properties.PropertyName') . ' = '
				. $this->db->quote('NAME')
			)
			->where(
				$this->db->quoteName('components.FormId') . ' = ' . $formId
			);
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	/**
	 * Function called by RSForm!Pro for different tasks.
	 *
	 * @return  void
	 *
	 * @since   2.12.0
	 * @throws  Exception
	 */
	public function onRsformFrontendSwitchTasks(): void
	{
		$plugin_task = $this->app->input->get('plugin_task');

		switch ($plugin_task)
		{
			case 'jdideal.notify':
				$this->rsfp_f_jdidealNotify();
				break;
			case 'jdideal.return':
				$formId = $this->app->input->getInt('formId');
				$this->jdidealReturn($formId);
				break;
		}
	}

	public function rsfp_f_onSwitchTasks(): void
	{
		$this->onRsformFrontendSwitchTasks();
	}

	/**
	 * Check the payment status.
	 *
	 * @return  boolean  True if payment is valid, otherwise false.
	 *
	 * @since   2.2.0
	 * @throws  Exception
	 * @throws  RuntimeException
	 * @throws  InvalidArgumentException
	 */
	public function rsfp_f_jdidealNotify(): bool
	{
		// Load the helper
		$jdideal = new Gateway;

		$trans  = $this->app->input->get('transactionId');
		$column = 'trans';

		if (empty($trans))
		{
			$trans  = $this->app->input->get('pid');
			$column = 'pid';
		}

		$details    = $jdideal->getDetails($trans, $column, false, 'rsformpro');
		$statusCode = $jdideal->getStatusCode($details->result);
		$jdideal->log('Transaction number: ' . $trans, $details->id);
		$jdideal->log('Details loaded ', $details->id);
		$jdideal->log('Details result: ' . $details->result, $details->id);
		$jdideal->log('Status code: ' . $statusCode, $details->id);
		$isValid = $jdideal->isValid($details->result);

		// Set the status
		switch ($statusCode)
		{
			case 'X':
				$statusValue = -1;
				break;
			case 'C':
				$statusValue = 1;
				break;
			default:
				$statusValue = 0;
				break;
		}

		$jdideal->log('Status value: ' . $statusValue, $details->id);

		// Get the IDs
		[$formId, $submissionId] = explode('.', $details->order_id);
		$formId       = (int) $formId;
		$submissionId = (int) $submissionId;

		// Check the payment status
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('FieldValue'))
			->from($this->db->quoteName('#__rsform_submission_values'))
			->where(
				$this->db->quoteName('SubmissionId') . ' = ' . $submissionId
			)
			->where($this->db->quoteName('FormId') . ' = ' . $formId)
			->where(
				$this->db->quoteName('FieldName') . ' =  ' . $this->db->quote(
					'_STATUS'
				)
			);
		$this->db->setQuery($query);
		$status = (int) $this->db->loadResult();

		$jdideal->log('RSForm status: ' . $status, $details->id);

		if ($status === 0)
		{
			$query->clear()
				->update($this->db->quoteName('#__rsform_submission_values'))
				->set(
					$this->db->quoteName('FieldValue') . ' = '
					. $this->db->quote($statusValue)
				)
				->where(
					$this->db->quoteName('SubmissionId') . ' = ' . $submissionId
				)
				->where($this->db->quoteName('FormId') . ' = ' . $formId)
				->where(
					$this->db->quoteName('FieldName') . ' =  '
					. $this->db->quote('_STATUS')
				);
			$this->db->setQuery($query)->execute();

			$jdideal->setProcessed(1, $details->id);

			$settings = $this->loadFormSettings($formId);

			if ($statusValue === 1
				|| (int) $settings->get(
					'sendEmailOnFailedPayment', 0
				) === 1)
			{
				$jdideal->log('Send out emails', $details->id);
				$this->sendConfirmationEmail($details, $formId, $submissionId);
				$this->app->triggerEvent(
					'onRsformAfterConfirmPayment', [$submissionId]
				);
			}
		}

		// Check if the result is valid
		if (!$isValid)
		{
			return false;
		}

		return true;
	}

	/**
	 * Send out a confirmation email with payment and submission details.
	 *
	 * @param   stdClass  $details       The payment details
	 * @param   int       $formId        The form the submission belongs to
	 * @param   int       $submissionId  The submission ID to send the email for
	 *
	 * @return  void
	 *
	 * @since   4.14.0
	 * @throws  Exception
	 */
	private function sendConfirmationEmail(
		stdClass $details,
		int $formId,
		int $submissionId
	): void {
		// Get the form parameters
		$settings = $this->loadFormSettings($formId);

		if ((int) $settings->get('confirmationEmail', 0) === 0)
		{
			return;
		}

		[$find, $replace] = RSFormProHelper::getReplacements($submissionId);

		// Load the values of the submission keep for B/C
		$query = $this->db->getQuery(true)
			->select(
				$this->db->quoteName(
					[
						'FieldValue',
						'FieldName',
					]
				)
			)
			->from($this->db->quoteName('#__rsform_submission_values'))
			->where(
				$this->db->quoteName('SubmissionId') . ' = ' . $submissionId
			);
		$this->db->setQuery($query);

		$values = $this->db->loadObjectList();

		array_map(
			static function ($field) use (&$find, &$replace) {
				$find[]    = '[' . strtoupper(trim($field->FieldName)) . ']';
				$replace[] = $field->FieldValue;
			},
			$values
		);

		// Build the placeholders
		$find[] = '[PAYMENT_METHOD]';
		$find[] = '[PAYMENT_ID]';
		$find[] = '[TRANSACTION_ID]';
		$find[] = '[CURRENCY]';
		$find[] = '[AMOUNT]';
		$find[] = '[CARD]';
		$find[] = '[RESULT]';

		$replace[] = Text::_(
			'COM_JDIDEALGATEWAY_PAYMENT_METHOD_' . $details->card
		);
		$replace[] = $details->paymentId;
		$replace[] = $details->trans;
		$replace[] = $details->currency;
		$replace[] = number_format(
			$details->amount,
			$settings->get('numberDecimals'),
			$settings->get('decimal'),
			$settings->get('thousands')
		);
		$replace[] = $details->card;
		$replace[] = $details->result;

		// Replace the placeholders
		$body    = str_ireplace(
			$find, $replace, $settings->get('confirmationMessage')
		);
		$subject = str_ireplace(
			$find, $replace, $settings->get('confirmationSubject')
		);

		// Instantiate the mailer
		$config   = Factory::getConfig();
		$from     = $config->get('mailfrom');
		$fromName = $config->get('fromname');
		$mail     = Factory::getMailer();
		$email    = explode(',', $settings->get('confirmationRecipient'));

		try
		{
			$mail->sendMail($from, $fromName, $email, $subject, $body, true);
		}
		catch (Exception $exception)
		{
			Factory::getApplication()->enqueueMessage(
				$exception->getMessage(), 'error'
			);
		}
	}

	/**
	 * Send a user back to the RSForms! Pro Thank you page.
	 *
	 * @param   int  $formId  The ID of the form to get the information from.
	 *
	 * @return  void
	 *
	 * @since   4.4.0
	 *
	 * @throws  Exception
	 */
	private function jdidealReturn(int $formId): void
	{
		// Get session object
		$session = Factory::getSession();

		// Load the helper
		$jdideal = new Gateway;

		$trans  = $this->app->input->get('transactionId');
		$column = 'trans';

		if (empty($trans))
		{
			$trans  = $this->app->input->get('pid');
			$column = 'pid';
		}

		$details = $jdideal->getDetails($trans, $column, false, 'rsformpro');

		// Check if we received a status, if not check again
		if (empty($details->result))
		{
			$this->rsfp_f_jdidealNotify();
		}

		// Get the form parameters
		$params = $this->loadFormSettings($formId);

		// Get data from session
		$formParams                = $session->get(
			'com_rsform.formparams.formId' . $formId
		);
		$formParams->formProcessed = true;

		if ((int) $params->get('showMessage') === 1)
		{
			// Show the result also used as fallback if there is no redirect information available
			[$replace, $with] = RSFormProHelper::getReplacements(
				$details->order_number
			);
			$message = $jdideal->getMessage($details->id);
			$message = str_ireplace(
				$replace, $with, $message
			);

			$this->app->triggerEvent(
				'onPrepareThankYouMessage', [&$message, $details, $formId]
			);

			$formParams->thankYouMessage = base64_encode($message);
		}

		$redirectUrl = $formParams->redirectUrl;

		$this->app->triggerEvent(
			'onPrepareRedirectUrl', [&$redirectUrl, $details, $formId]
		);

		$jdideal->log('Redirect to ' . $redirectUrl, $details->id);
		$this->app->redirect($redirectUrl);
	}

	/**
	 * Send the submission emails.
	 *
	 * @param   int  $submissionId  The submission ID.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onRsformAfterConfirmPayment(int $submissionId): void
	{
		// Only send out emails if the RSForm! Pro Payment plugin is disabled
		if (!PluginHelper::isEnabled('system', 'rsfppayment'))
		{
			RSFormProHelper::sendSubmissionEmails($submissionId);
		}
	}

	public function rsfp_afterConfirmPayment(int $submissionId): void
	{
		$this->onRsformAfterConfirmPayment($submissionId);
	}

	/**
	 * The name of the component.
	 *
	 * @return  void
	 *
	 * @since   2.12
	 */
	public function jdidealScreen(): void
	{
		echo 'RO Payments';
	}

	/**
	 * Enhance the condition option fields.
	 *
	 * @param   array  $args  The array of condition options.
	 *
	 * @return  void
	 *
	 * @since   2.12.0
	 */
	public function onRsformBackendCreateConditionOptionFields(array $args): void
	{
		$args['types'][] = ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT;
		$args['types'][] = ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS;
		$args['types'][] = ROPAYMENTS_FIELD_PAYMENT_INPUT;
	}

	public function rsfp_bk_onCreateConditionOptionFields(array $args): void
	{
		$this->onRsformBackendCreateConditionOptionFields($args);
	}

	/**
	 * Modify values for conditional fields.
	 *
	 * @param   array  $options  The field option
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	public function onRsformBackendCreateConditionOptionFieldItem(array $options): void
	{
		switch ($options['field']->ComponentTypeId)
		{
			case ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS:
				$options['item']->value = $options['item']->label;
				break;
		}
	}

	/**
	 * Update the conditions after form save.
	 *
	 * @param   TableRSForm_Forms  $form  The form object that is being stored.
	 *
	 * @return  boolean  True on success | False on failure
	 *
	 * @since   2.12.0
	 * @throws  Exception
	 * @throws  RuntimeException
	 */
	public function onRsformFormSave(TableRSForm_Forms $form): bool
	{
		// Get the language the form is being stored in
		$storeLanguage = $this->app->input->post->get('Language');
		$baseLanguage  = $this->app->input->post->get('Lang');
		$translate     = false;
		$formId        = (int) $form->FormId;

		if ($storeLanguage !== $baseLanguage)
		{
			$translate = true;
		}

		// Load the values
		$query = $this->db->getQuery(true)
			->select(
				$this->db->quoteName('condition_details.id', 'conditionId')
				. ', ' .
				$this->db->quoteName('condition_details.value') . ', ' .
				$this->db->quoteName('condition_details.component_id')
			)
			->from(
				$this->db->quoteName(
					'#__rsform_components', 'rsform_components'
				)
			)
			->leftJoin(
				$this->db->quoteName(
					'#__rsform_condition_details', 'condition_details'
				)
				. ' ON ' . $this->db->quoteName(
					'condition_details.component_id'
				) . ' = ' . $this->db->quoteName(
					'rsform_components.ComponentId'
				)
			)
			->where(
				$this->db->quoteName('rsform_components.FormId') . ' = '
				. $formId
			)
			->where(
				$this->db->quoteName('rsform_components.ComponentTypeId')
				. ' = 5576'
			)
			->order($this->db->quoteName('conditionId'));
		$this->db->setQuery($query);

		$conditions = $this->db->loadObjectList();

		// Load the replacements
		$query->clear()
			->select(
				$this->db->quoteName(
					[
						'rsform_properties.PropertyValue',
						'rsform_properties.PropertyName',
						'rsform_properties.ComponentId',
						'rsform_components.ComponentTypeId',
					]
				)
			)
			->from(
				$this->db->quoteName(
					'#__rsform_properties', 'rsform_properties'
				)
			)
			->leftJoin(
				$this->db->quoteName(
					'#__rsform_components', 'rsform_components'
				)
				. ' ON ' . $this->db->quoteName('rsform_properties.ComponentId')
				. ' = ' . $this->db->quoteName(
					'rsform_components.ComponentId'
				)
			)
			->where(
				$this->db->quoteName('rsform_components.FormId') . ' = '
				. $formId
			)
			->where(
				'(' . $this->db->quoteName('rsform_properties.PropertyName')
				. ' = ' . $this->db->quote('DEFAULTVALUE')
				. ' OR ' .
				$this->db->quoteName('rsform_properties.PropertyName') . ' = '
				. $this->db->quote('ITEMS')
				. ')'
			)
			->where(
				$this->db->quoteName('rsform_components.ComponentTypeId')
				. ' = 5576'
			);
		$this->db->setQuery($query);

		$replacements = $this->db->loadObjectList();

		// Prepare the replacements
		foreach ($replacements as $index => $replacement)
		{
			// Check if we need to get a translation
			if ($translate)
			{
				$query->clear()
					->select($this->db->quoteName('value'))
					->from($this->db->quoteName('#__rsform_translations'))
					->where($this->db->quoteName('form_id') . ' = ' . $formId)
					->where(
						$this->db->quoteName('lang_code') . ' = '
						. $this->db->quote($storeLanguage)
					)
					->where(
						$this->db->quoteName('reference') . ' = '
						. $this->db->quote('properties')
					)
					->where(
						$this->db->quoteName('reference_id') . ' = '
						. $this->db->quote(
							$replacement->ComponentId . '.'
							. $replacement->PropertyName
						)
					);
				$this->db->setQuery($query);

				$replacement->PropertyValue = $this->db->loadResult();
			}

			$replacement->PropertyValue = RSFormProHelper::isCode(
				$replacement->PropertyValue
			);
			$replacement->PropertyValue = str_replace(
				["\r\n", "\r"], "\n", $replacement->PropertyValue
			);
			$replacement->PropertyValue = str_replace(
				['[c]', '[g]'], '', $replacement->PropertyValue
			);
			$replacement->PropertyValue = explode(
				"\n", $replacement->PropertyValue
			);

			$replacements[$index] = $replacement;
		}

		// Check if we have any condition with the value
		foreach ($conditions as $condition)
		{
			foreach ($replacements as $replacement)
			{
				if ($condition->component_id === $replacement->ComponentId)
				{
					foreach (
						$replacement->PropertyValue as $index => $propertyValue
					)
					{
						$propertyValue = explode('|', $propertyValue, 2);

						if ($condition->value === $propertyValue[0])
						{
							// We have a met condition, need to update the value
							$query->clear()
								->update(
									$this->db->quoteName(
										'#__rsform_condition_details'
									)
								)
								->set(
									$this->db->quoteName('value') . ' = '
									. $this->db->quote(trim($propertyValue[1]))
								)
								->where(
									$this->db->quoteName('id') . ' = '
									. (int) $condition->conditionId
								);
							$this->db->setQuery($query)->execute();

							break;
						}
					}
				}
			}
		}

		// Get the form settings
		$settings = $this->app->input->post->get(
			'roPaymentsParams', [], 'array'
		);
		$tables   = $this->db->getTableList();
		$table    = $this->db->getPrefix() . 'rsform_jdideal';

		if (!in_array($table, $tables, true))
		{
			return true;
		}

		$query->clear()
			->select($this->db->quoteName('params'))
			->from($this->db->quoteName('#__rsform_jdideal'))
			->where($this->db->quoteName('form_id') . ' = ' . $formId);
		$this->db->setQuery($query);
		$params = $this->db->loadResult();

		if ($params)
		{
			$query->clear()
				->update($this->db->quoteName('#__rsform_jdideal'))
				->set(
					$this->db->quoteName('params') . ' = ' . $this->db->quote(
						json_encode($settings)
					)
				)
				->where($this->db->quoteName('form_id') . ' = ' . $formId);
		}
		else
		{
			$query->clear()
				->insert($this->db->quoteName('#__rsform_jdideal'))
				->columns(['form_id', 'params'])
				->values(
					$formId . ',' . $this->db->quote(json_encode($settings))
				);
		}

		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}

	public function rsfp_onFormSave(TableRSForm_Forms $form): bool
	{
		return $this->onRsformFormSave($form);
	}

	/**
	 * Tell RSForm!Pro that we handle payments.
	 *
	 * @param   array    $items   The list of payment methods
	 * @param   integer  $formId  The ID of the form
	 *
	 * @return  void
	 *
	 * @since   2.8.0
	 */
	public function onRsformGetPayment(array &$items, int $formId): void
	{
		if ($components = RSFormProHelper::componentExists(
			$formId, $this->componentId
		))
		{
			foreach ($components as $component)
			{
				$data = RSFormProHelper::getComponentProperties($component);

				$item        = new stdClass;
				$item->value = $data['NAME'];
				$item->text  = $data['LABEL'];

				$items[] = $item;
			}
		}
	}

	public function rsfp_getPayment(array &$items, int $formId): void
	{
		$this->onRsformGetPayment($items, $formId);
	}

	/**
	 * Section to deal with integrated payment option for the Payment Package
	 */

	/**
	 * Load the RO Payments Form now the form has been submitted. This is used for the payment package.
	 * For the RO Payments buttons see the rsfp_f_onAfterFormProcess() method.
	 *
	 * @param   string   $payValue      The name of the payment method to execute.
	 * @param   integer  $formId        The ID of the form submitted.
	 * @param   integer  $submissionId  The ID of the submission.
	 * @param   float    $price         The price to pay.
	 * @param   array    $products      The list of products.
	 * @param   string   $code          Unknown code.
	 *
	 * @return  mixed  Return nothing we don't process the payment or price is 0, redirect if there is to be paid
	 *
	 * @since   2.2.0
	 * @throws  Exception
	 * @see     rsfp_f_onAfterFormProcess
	 *
	 */
	public function onRsformDoPayment(
		string $payValue,
		int $formId,
		int $submissionId,
		?float $price,
		array $products,
		string $code
	) {
		// Execute only our plugin
		$match      = false;
		$components = RSFormProHelper::componentExists(
			$formId, $this->componentId
		);

		foreach ($components as $component)
		{
			$data = RSFormProHelper::getComponentProperties($component);

			if ($data['NAME'] === $payValue)
			{
				$match = true;
			}
		}

		if (!$match)
		{
			if ($payValue !== 'jdidealButton')
			{
				return;
			}

			$payValue = '';
		}

		if ($price !== null && $price > 0)
		{
			// Get the form parameters
			$settings = $this->loadFormSettings($formId);

			// Construct the feedback URLs
			$itemId = $this->app->input->getInt('Itemid', 0);

			$uri = Uri::getInstance(
				Route::_(Uri::root() . 'index.php?option=com_rsform')
			);
			$uri->setVar('formId', $formId);
			$uri->setVar('task', 'plugin');
			$uri->setVar('plugin_task', 'jdideal.return');
			$uri->setVar('Itemid', $this->app->input->get('Itemid'));
			$returnUrl = $uri->toString();

			$uri = Uri::getInstance(
				Route::_(Uri::root() . 'index.php?option=com_rsform')
			);
			$uri->setVar('formId', $formId);
			$uri->setVar('task', 'plugin');
			$uri->setVar('plugin_task', 'jdideal.notify');
			$uri->setVar('code', $code);
			$uri->setVar('Itemid', $this->app->input->get('Itemid'));
			$notifyUrl = $uri->toString();

			// Calculate price with tax
			if ($settings->get('taxValue', 0) > 0)
			{
				$price = $settings->get('taxType', 0)
					? $price + $settings->get('taxValue', 0)
					: $price * ($settings->get(
							'taxValue',
							0
						) / 100 + 1);
			}

			// Load the payment provider
			$profileAlias = $this->getProfileAlias($formId);

			// Load the custom order number
			$orderNumber = $this->getCustomOrderNumber(
				['formId' => $formId, 'SubmissionId' => $submissionId]
			);

			// Get the email field
			$email = $this->getEmailField(
				['formId' => $formId, 'SubmissionId' => $submissionId]
			);

			// Set some needed data
			$data = [
				'amount'         => $price,
				'order_number'   => $orderNumber,
				'order_id'       => $formId . '.' . $submissionId,
				'origin'         => 'rsformpro',
				'return_url'     => $returnUrl,
				'notify_url'     => $notifyUrl,
				'cancel_url'     => '',
				'email'          => $email,
				'payment_method' => $payValue,
				'currency'       => $params->currency ?? '',
				'profileAlias'   => $profileAlias,
				'custom_html'    => '',
				'silent'         => false,
			];

			// Show a loading message in case it takes some time
			echo Text::_('PLG_RSFP_JDIDEAL_LOADING');

			// Build the form to redirect to RO Payments
			?>
			<form id="jdideal" action="<?php
			echo Route::_(
				'index.php?option=com_jdidealgateway&view=checkout&Itemid='
				. $itemId
			); ?>" method="post">
				<input type="hidden" name="vars" value="<?php
				echo base64_encode(json_encode($data)); ?>"/>
			</form>
			<script type="text/javascript">
              document.getElementById('jdideal').submit()
			</script>
			<?php
			$this->app->close();
		}
		else
		{
			$this->rsfp_f_onAfterFormProcess(
				[
					'formId'       => $formId,
					'SubmissionId' => $submissionId,
					'internal'     => true,
				]
			);
		}
	}

	public function rsfp_doPayment(
		string $payValue,
		int $formId,
		int $submissionId,
		?float $price,
		array $products,
		string $code
	) {
		return $this->onRsformDoPayment($payValue, $formId, $submissionId, $price, $products, $code);
	}

	/**
	 * Load the payment provider from the form.
	 *
	 * @param   int  $formId  The form ID
	 *
	 * @return  string  The name of the payment provider.
	 *
	 * @since   4.0.0
	 * @throws  RuntimeException
	 */
	private function getProfileAlias(int $formId): string
	{
		$settings = $this->loadFormSettings($formId);

		return $settings->get('profileAlias', '');
	}

	/**
	 * Load the custom order number.
	 *
	 * @param   array  $args  List of arguments of the submission
	 *
	 * @return  string  The order number.
	 *
	 * @since   2.12.0
	 *
	 * @throws  RuntimeException
	 */
	private function getCustomOrderNumber(array $args): string
	{
		$db          = $this->db;
		$formId      = (int) $args['formId'];
		$orderNumber = false;
		$settings    = $this->loadFormSettings($formId);

		if ($fieldOrderNumber = $settings->get('fieldOrderNumber'))
		{
			$query = $db->getQuery(true)
				->select($db->quoteName('FieldValue'))
				->from($db->quoteName('#__rsform_submission_values'))
				->where($db->quoteName('FormId') . ' = ' . $formId)
				->where(
					$db->quoteName('SubmissionId') . ' = '
					. (int) $args['SubmissionId']
				)
				->where(
					$db->quoteName('FieldName') . ' = ' . $db->quote(
						$fieldOrderNumber
					)
				);
			$db->setQuery($query);
			$orderNumber = $db->loadResult();
		}

		if (!$orderNumber)
		{
			// If no custom order number is set, we use the submission ID
			$orderNumber = $args['SubmissionId'];
		}

		return (string) $orderNumber;
	}

	/**
	 * Load the email field.
	 *
	 * @param   array  $args  List of arguments of the submission
	 *
	 * @return  string  The email address.
	 *
	 * @since   2.12.0
	 * @throws  RuntimeException
	 */
	private function getEmailField(array $args): string
	{
		$formId   = (int) $args['formId'];
		$email    = '';
		$settings = $this->loadFormSettings($formId);

		if ($fieldEmail = $settings->get('fieldEmail', false))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('FieldValue'))
				->from($this->db->quoteName('#__rsform_submission_values'))
				->where($this->db->quoteName('FormId') . ' = ' . $formId)
				->where(
					$this->db->quoteName('SubmissionId') . ' = '
					. (int) $args['SubmissionId']
				)
				->where(
					$this->db->quoteName('FieldName') . ' = '
					. $this->db->quote($fieldEmail)
				);
			$this->db->setQuery($query);
			$email = $this->db->loadResult();
		}

		return $email ?? '';
	}

	/**
	 * Check if we need to defer the email for the user.
	 *
	 * @param   array  $args  The form data
	 *
	 * @return  void
	 *
	 * @since   2.2.0
	 * @throws  Exception
	 * @throws  RuntimeException
	 */
	public function onRsformFrontendAfterFormProcess(array $args): void
	{
		if (!$this->canRun())
		{
			return;
		}

		// Get the payment package payment selector if it exists
		if (!array_key_exists('internal', $args)
			&& RSFormProHelper::componentExists($args['formId'], 27))
		{
			return;
		}

		$formId = (int) $args['formId'];

		if (RSFormProHelper::componentExists($formId, $this->ropaymentsFields))
		{
			$price    = (float) 0;
			$settings = $this->loadFormSettings($formId);
			$total    = RSFormProHelper::componentExists($formId, 5577);

			if (empty($total))
			{
				throw new InvalidArgumentException(
					Text::_('PLG_RSFP_JDIDEAL_TOTAL_FIELD_IS_MISSING')
				);
			}

			$totalDetails     = RSFormProHelper::getComponentProperties(
				$total[0]
			);
			$singleProduct    = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT);
			$multipleProducts = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS);
			$inputBoxes       = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_INPUT);

			// Get the price
			if ($multipleProducts || $inputBoxes || $singleProduct || $total)
			{
				$price = (float) $this->getSubmissionValue(
					$args['SubmissionId'], $totalDetails['componentId']
				);
			}

			if ($price > 0)
			{
				$itemId = $this->app->input->getInt('Itemid', 0);
				$lang   = substr($this->app->input->get('lang'), 0, 2);

				// Create the feedback URLs
				$uri = Uri::getInstance(
					Route::_(Uri::root() . 'index.php?option=com_rsform')
				);
				$uri->setVar('formId', $formId);
				$uri->setVar('task', 'plugin');
				$uri->setVar('plugin_task', 'jdideal.return');
				$uri->setVar('Itemid', $itemId);
				$uri->setVar('lang', $lang);
				$returnUrl = $uri->toString();

				$uri = Uri::getInstance(
					Route::_(Uri::root() . 'index.php?option=com_rsform')
				);
				$uri->setVar('formId', $formId);
				$uri->setVar('task', 'plugin');
				$uri->setVar('plugin_task', 'jdideal.notify');
				$uri->setVar('Itemid', $itemId);
				$notifyUrl = $uri->toString();

				// Load the payment provider
				$profileAlias = $this->getProfileAlias($formId);

				// Get the custom order number field
				$orderNumber = $this->getCustomOrderNumber($args);

				// Get the email field
				$email = $this->getEmailField($args);

				$paymentMethod = $this->getSubmissionValue(
					$args['SubmissionId'], $this->getComponentIdChoosePayment($formId)
				);

				// Set some needed data
				$data = [
					'amount'         => $price,
					'order_number'   => $orderNumber,
					'order_id'       => $formId . '.' . $args['SubmissionId'],
					'origin'         => 'rsformpro',
					'return_url'     => $returnUrl,
					'notify_url'     => $notifyUrl,
					'cancel_url'     => '',
					'email'          => $email,
					'payment_method' => $paymentMethod,
					'currency'       => $settings->get('currency', ''),
					'profileAlias'   => $profileAlias,
					'custom_html'    => '',
					'silent'         => false,
				];

				// Show a loading message in case it takes some time
				echo Text::_('PLG_RSFP_JDIDEAL_LOADING');

				// Build the form to redirect to RO Payments
				?>
				<form id="jdideal" action="<?php
				echo Route::_(
					'index.php?option=com_jdidealgateway&view=checkout&Itemid='
					. $itemId
				); ?>" method="post">
					<input type="hidden" name="vars" value="<?php
					echo base64_encode(json_encode($data)); ?>"/>
				</form>
				<script type="text/javascript">
                  document.getElementById('jdideal').submit()
				</script>
				<?php
				$this->app->close();
			}
			elseif ($price === 0.00
				&& (int) $settings->get('allowEmpty', 0) === 1)
			{
				// Don't do anything to allow an empty price checkout. RSForms will send the emails.
				return;
			}
			else
			{
				$this->app->enqueueMessage(
					Text::_('PLG_RSFP_JDIDEAL_NO_PRICE_RECEIVED'), 'error'
				);
			}
		}
	}

	public function rsfp_f_onAfterFormProcess(array $args): void
	{
		$this->onRsformFrontendAfterFormProcess($args);
	}

	/**
	 * Get the value of the field as submitted by the user.
	 *
	 * @param   int  $submissionId  The ID of the submitted entry
	 * @param   int  $componentId   The ID of the component the value was submitted for
	 *
	 * @return  mixed  The return value or null if the query failed.
	 *
	 * @since   2.12.0
	 * @throws  RuntimeException
	 */
	public function getSubmissionValue(int $submissionId, int $componentId)
	{
		$name = $this->getPropertyValue($componentId, 'NAME');

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('FieldValue'))
			->from($this->db->quoteName('#__rsform_submission_values'))
			->where(
				$this->db->quoteName('SubmissionId') . ' = ' . $submissionId
			)
			->where(
				$this->db->quoteName('FieldName') . ' = ' . $this->db->quote(
					$name
				)
			);
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	/**
	 * Get the component name for a given ID
	 *
	 * @param   integer  $componentId   The component ID to get the value for
	 * @param   string   $propertyName  The property name to get the value for
	 *
	 * @return  string The value found in the database
	 *
	 * @since   7.0.0
	 * @see     rsfp_doPayment
	 */
	private function getPropertyValue(int $componentId, string $propertyName): string
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('PropertyValue'))
			->from($this->db->quoteName('#__rsform_properties'))
			->where($this->db->quoteName('ComponentId') . ' = ' . $componentId)
			->where(
				$this->db->quoteName('PropertyName') . ' = ' . $this->db->quote($propertyName)
			);
		$this->db->setQuery($query);

		return (string) $this->db->loadResult();
	}

	/**
	 * Make it possible to translate the values in the email.
	 *
	 * @param   array  $args  An array with values to be translated.
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 */
	public function onRsformAfterCreatePlaceholders(array $args): void
	{
		$formId = $args['form']->FormId;

		if (!$this->hasRopaymentsFields($formId))
		{
			return;
		}

		foreach ($args['values'] as $key => $value)
		{
			if (!strpos($value, ','))
			{
				$args['values'][$key] = Text::_(nl2br($value));
			}
		}

		$translations     = RSFormProHelper::getTranslations('properties', $formId, $args['submission']->Lang);
		$singleProduct    = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT);
		$multipleProducts = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS);
		$total            = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_TOTAL);
		$inputProducts    = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_INPUT);
		$choosePayment    = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_CHOOSE);
		$discountField    = RSFormProHelper::componentExists($formId, ROPAYMENTS_FIELD_PAYMENT_DISCOUNT);

		if (!empty($singleProduct))
		{
			$this->createSingleProductPlaceHolders($singleProduct, $translations, $args);
		}

		if (!empty($multipleProducts))
		{
			$this->createMultipleProductsPlaceHolders($multipleProducts, $translations, $args);
		}

		if (!empty($total))
		{
			$this->createTotalPlaceHolders($total, $translations, $args);
		}

		if (!empty($inputProducts))
		{
			$this->createInputProductsPlaceHolders($inputProducts, $translations, $args);
		}

		if (!empty($choosePayment))
		{
			$this->createChoosePaymentPlaceHolders($choosePayment, $args);
		}

		if (!empty($discountField))
		{
			$this->createDiscountPlaceHolders($discountField, $args);
		}

		$args['placeholders'][] = '{_STATUS:value}';
		$args['values'][]       = isset($args['submission']->values['_STATUS'])
			? Text::_('PLG_RSFP_JDIDEAL_PAYMENT_STATUS_' . $args['submission']->values['_STATUS'])
			: '';

		$args['placeholders'][] = '{_STATUS:caption}';
		$args['values'][]       = Text::_('PLG_RSFP_JDIDEAL_PAYMENT_STATUS');

		$args['placeholders'][] = '{_TRANSACTION_ID:value}';
		$args['values'][]       = $args['submission']->values['_TRANSACTION_ID'] ?? '';

		$args['placeholders'][] = '{_TRANSACTION_ID:caption}';
		$args['values'][]       = Text::_('PLG_RSFP_ROPAYMENTS_TRANSACTION_ID');
	}

	/**
	 * Create the necessary placeholders.
	 *
	 * @param   mixed  $singleProduct  The product details
	 * @param   array  $translations   The list of translations
	 * @param   array  $args           The form arguments
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private function createSingleProductPlaceHolders($singleProduct, $translations, &$args)
	{
		$formId      = (int) $args['form']->FormId;
		$properties  = RSFormProHelper::getComponentProperties($singleProduct, false);
		$priceHelper = new Price($this->loadFormSettings($formId));

		$this->translate($properties, $translations);

		$data  = $properties[$singleProduct[0]];
		$price = $data['PRICE'];

		$args['placeholders'][] = '{' . $data['NAME'] . ':price}';
		$args['values'][]       = $priceHelper->getPriceMask($data['CAPTION'], $price, ($data['CURRENCY'] ?? ''));
		$args['placeholders'][] = '{' . $data['NAME'] . ':amount}';
		$args['values'][]       = $priceHelper->getAmountMask($price, ($data['CURRENCY'] ?? ''));
	}

	/**
	 * Create the necessary placeholders.
	 *
	 * @param   mixed  $multipleProducts  The product details
	 * @param   array  $translations      The list of translations
	 * @param   array  $args              The form arguments
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private function createMultipleProductsPlaceHolders($multipleProducts, $translations, &$args)
	{
		$formId            = (int) $args['form']->FormId;
		$submissionId      = (int) $args['submission']->SubmissionId;
		$multipleSeparator = nl2br($args['form']->MultipleSeparator);
		$properties        = RSFormProHelper::getComponentProperties($multipleProducts, false);
		$priceHelper       = new Price($this->loadFormSettings($formId));
		$this->translate($properties, $translations);

		foreach ($multipleProducts as $product)
		{
			$data  = $properties[$product];
			$value = $this->getSubmissionValue($submissionId, (int) $product);

			if ($value === '' || $value === null)
			{
				$args['placeholders'][] = '{' . $data['NAME'] . ':amount}';
				$args['values'][]       = '';

				continue;
			}

			$values = explode("\n", $value);

			$field = new MultipleProducts(
				[
					'formId'      => $formId,
					'componentId' => $product,
					'data'        => $data,
					'value'       => ['formId' => $formId, $data['NAME'] => $values],
					'invalid'     => false,
				]
			);

			$replace    = '{' . $data['NAME'] . ':price}';
			$with       = [];
			$withAmount = [];

			if ($items = $field->getItems())
			{
				foreach ($items as $item)
				{
					if (empty($item))
					{
						continue;
					}

					$item = new RSFormProFieldItem($item);

					foreach ($values as $value)
					{
						if (stristr($value, $item->label))
						{
							$with[]   = $priceHelper->getPriceMask(
								$value, $item->value, ($data['CURRENCY'] ?? '')
							);
							$quantity = trim(str_ireplace($item->label, '', $value));

							if (strlen($quantity) === 0)
							{
								$quantity = 1;
							}

							$withAmount[] = $quantity * $item->value;
						}
					}
				}
			}

			if (($position = array_search($replace, $args['placeholders'])) !== false)
			{
				$args['placeholders'][$position] = $replace;
				$args['values'][$position]       = implode($multipleSeparator, $with);
			}
			else
			{
				$args['placeholders'][] = $replace;
				$args['values'][]       = implode($multipleSeparator, $with);
			}

			$args['placeholders'][] = '{' . $data['NAME'] . ':amount}';
			$args['values'][]       = $priceHelper->getAmountMask(
				($withAmount ? array_sum($withAmount) : 0), ($data['CURRENCY'] ?? '')
			);
		}
	}

	/**
	 * Create the necessary placeholders.
	 *
	 * @param   mixed  $total         The total details
	 * @param   array  $translations  The list of translations
	 * @param   array  $args          The form arguments
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private function createTotalPlaceHolders($total, $translations, &$args)
	{
		$formId       = (int) $args['form']->FormId;
		$submissionId = (int) $args['submission']->SubmissionId;
		$price        = $this->getSubmissionValue($submissionId, $total[0]);
		$properties   = RSFormProHelper::getComponentProperties($total, false);
		$priceHelper  = new Price($this->loadFormSettings($formId));

		$this->translate($properties, $translations);

		$data = $properties[$total[0]];

		$args['placeholders'][] = '{' . $data['NAME'] . ':price}';
		$args['values'][]       = $priceHelper->getTotalMask($price, ($data['CURRENCY'] ?? ''), $formId, $submissionId);
		$args['placeholders'][] = '{' . $data['NAME'] . ':amount}';
		$args['values'][]       = $priceHelper->getAmountMask($price, ($data['CURRENCY'] ?? ''));
	}

	/**
	 * Create the necessary placeholders.
	 *
	 * @param   mixed  $inputProducts  The product details
	 * @param   array  $translations   The list of translations
	 * @param   array  $args           The form arguments
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private function createInputProductsPlaceHolders($inputProducts, $translations, &$args)
	{
		$formId       = (int) $args['form']->FormId;
		$submissionId = (int) $args['submission']->SubmissionId;
		$properties   = RSFormProHelper::getComponentProperties($inputProducts, false);
		$priceHelper  = new Price($this->loadFormSettings($formId));
		$this->translate($properties, $translations);

		foreach ($inputProducts as $componentId)
		{
			$price = $this->getSubmissionValue($submissionId, $componentId);
			$data  = $properties[$componentId];

			$args['placeholders'][] = '{' . $data['NAME'] . ':price}';
			$args['values'][]       = $priceHelper->getPriceMask($data['CAPTION'], $price, ($data['CURRENCY'] ?? ''));
			$args['placeholders'][] = '{' . $data['NAME'] . ':amount}';
			$args['values'][]       = $priceHelper->getAmountMask(($price ?? 0), ($data['CURRENCY'] ?? ''));
		}
	}

	/**
	 * Create the necessary placeholders for a payment package field.
	 *
	 * @param   mixed  $choosePayment  The product details
	 * @param   array  $args           The form arguments
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private function createChoosePaymentPlaceHolders($choosePayment, &$args)
	{
		if (empty($choosePayment))
		{
			return;
		}

		$formId       = (int) $args['form']->FormId;
		$submissionId = (int) $args['submission']->SubmissionId;
		$data         = RSFormProHelper::getComponentProperties($choosePayment[0]);
		$items        = $this->getPayments($formId);
		$value        = $this->getSubmissionValue($submissionId, $choosePayment[0]);
		$text         = '';

		if ($items)
		{
			foreach ($items as $item)
			{
				if ($item->value == $value)
				{
					$text = $item->text;
					break;
				}
			}
		}

		$args['placeholders'][] = '{' . $data['NAME'] . ':text}';
		$args['values'][]       = $text;
	}

	/**
	 * Create the necessary placeholders.
	 *
	 * @param   mixed  $discountField  The product details
	 * @param   array  $args           The form arguments
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private function createDiscountPlaceHolders($discountField, &$args)
	{
		$submissionId           = (int) $args['submission']->SubmissionId;
		$args['placeholders'][] = '{discount}';
		$args['placeholders'][] = '{discountprice}';

		$discount      = '';
		$discountPrice = '';

		$data = RSFormProHelper::getComponentProperties($discountField[0], false);

		if ($codes = RSFormProHelper::isCode($data['COUPONS']))
		{
			$usedCode    = $this->getSubmissionValue($submissionId, $discountField[0]);
			$formId      = (int) $args['submission']->FormId;
			$componentId = $this->getComponentIdByType($formId, ROPAYMENTS_FIELD_PAYMENT_TOTAL);
			$codes       = RSFormProHelper::explode($codes);

			foreach ($codes as $string)
			{
				if (strpos($string, '|') === false)
				{
					continue;
				}

				[$value, $code] = explode('|', $string, 2);

				if ($code === $usedCode)
				{
					$discount = $calculatedDiscount = $value;

					if (strpos($value, '%') !== false)
					{
						$price = $this->getSubmissionValue($submissionId, $componentId);
						$value = (float) trim($value, '%');

						if (is_numeric($value))
						{
							if ($value != 100)
							{
								$calculatedDiscount = ($price / (100 - $value)) * $value;
							}
						}
					}

					$discountPrice = $this->numberFormat($calculatedDiscount);

					break;
				}
			}
		}

		$args['values'][] = $discount;
		$args['values'][] = $discountPrice;
	}

	/**
	 * Translate the field values.
	 *
	 * @param   array  $properties    The properties to translate
	 * @param   array  $translations  The translations
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private function translate(&$properties, $translations): void
	{
		foreach ($properties as $componentId => $componentProperties)
		{
			foreach ($componentProperties as $property => $value)
			{
				$referenceId = $componentId . '.' . $property;

				if (isset($translations[$referenceId]))
				{
					$componentProperties[$property] = $translations[$referenceId];
				}
			}

			$properties[$componentId] = $componentProperties;
		}
	}


	/**
	 * Get a list of payment options.
	 *
	 * @return  array  List of payments.
	 *
	 * @since   7.0.0
	 */
	private function getPayments($formId): array
	{
		$items = [];

		Factory::getApplication()->triggerEvent('onRsformGetPayment', [&$items, $formId]);

		return $items;
	}

	/**
	 * Format the discount number.
	 *
	 * @param   string  $value  The value to format
	 *
	 * @return  string  Formatted value.
	 *
	 * @since   7.0.0
	 */
	private function numberFormat($value): string
	{
		return number_format(
			(float) $value,
			RSFormProHelper::getConfig('payment.nodecimals'),
			RSFormProHelper::getConfig('payment.decimal'),
			RSFormProHelper::getConfig('payment.thousands')
		);
	}

	public function rsfp_onAfterCreatePlaceholders(array $vars): void
	{
		$this->onRsformAfterCreatePlaceholders($vars);
	}

	/**
	 * Add the option under Extras when editing the form properties.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onRsformBackendAfterShowFormEditTabsTab(): void
	{
		?>
		<li>
			<?php
			echo HTMLHelper::_(
				'link',
				'javascript: void(0);',
				'<span class="rsficon jdicon-jdideal"></span><span class="inner-text">'
				. Text::_(
					'PLG_RSFP_JDIDEAL_LABEL'
				) . '</span>'
			);
			?>
		</li>
		<?php
	}

	public function rsfp_bk_onAfterShowFormEditTabsTab(): void
	{
		$this->onRsformBackendAfterShowFormEditTabsTab();
	}

	/**
	 * Add settings to defer sending of emails.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	public function onRsformBackendAfterShowFormEditTabs(): void
	{
		$formId = $this->app->input->getInt('formId');
		$tables = $this->db->getTableList();
		$table  = $this->db->getPrefix() . 'rsform_jdideal';

		if (!in_array($table, $tables, true))
		{
			return;
		}

		// Load the settings
		$settings = $this->loadFormSettings($formId);

		$form = new Form('ropayments');
		$form->loadFile(__DIR__ . '/configuration.xml');
		$form->bind(['roPaymentsParams' => $settings->toArray()]);

		HTMLHelper::_('formbehavior.chosen');

		?>
		<div id="ropayments" class="form-horizontal <?php
		echo Version::MAJOR_VERSION === 3 ? 'ropayments3' : ''; ?>">
			<?php
			echo HTMLHelper::_(
				'bootstrap.startTabSet', 'ropayments-config',
				['active' => 'ropayments-general']
			);
			echo HTMLHelper::_(
				'bootstrap.addTab',
				'ropayments-config',
				'ropayments-general',
				Text::_('PLG_RSFP_JDIDEAL_CONFIG_GENERAL')
			);
			echo $form->renderField('profileAlias', 'roPaymentsParams');
			echo $form->renderField('currency', 'roPaymentsParams');
			echo $form->renderField('allowEmpty', 'roPaymentsParams');
			echo $form->renderField('showMessage', 'roPaymentsParams');
			echo HTMLHelper::_('bootstrap.endTab');
			echo HTMLHelper::_(
				'bootstrap.addTab',
				'ropayments-config',
				'ropayments-currency',
				Text::_('PLG_RSFP_JDIDEAL_CONFIG_PRICE')
			);
			echo $form->renderField('thousands', 'roPaymentsParams');
			echo $form->renderField('decimal', 'roPaymentsParams');
			echo $form->renderField('numberDecimals', 'roPaymentsParams');
			echo $form->renderField('priceMask', 'roPaymentsParams');
			echo $form->renderField('amountMask', 'roPaymentsParams');
			echo '<div class="totalPriceMask">';
			echo $form->renderField('totalMask', 'roPaymentsParams');
			echo '</div>';
			echo $form->renderField('taxType', 'roPaymentsParams');
			echo $form->renderField('taxValue', 'roPaymentsParams');
			echo HTMLHelper::_('bootstrap.endTab');
			echo HTMLHelper::_(
				'bootstrap.addTab',
				'ropayments-config',
				'ropayments-fields',
				Text::_('PLG_RSFP_JDIDEAL_CONFIG_FIELDS')
			);
			echo $form->renderField('fieldOrderNumber', 'roPaymentsParams');
			echo $form->renderField('fieldName', 'roPaymentsParams');
			echo $form->renderField('fieldEmail', 'roPaymentsParams');
			echo HTMLHelper::_('bootstrap.endTab');
			echo HTMLHelper::_(
				'bootstrap.addTab',
				'ropayments-config',
				'ropayments-emails',
				Text::_('PLG_RSFP_JDIDEAL_CONFIG_MAIL')
			);
			echo $form->renderField('userEmail', 'roPaymentsParams');
			echo $form->renderField('adminEmail', 'roPaymentsParams');
			echo $form->renderField('additionalEmails', 'roPaymentsParams');
			echo $form->renderField(
				'sendEmailOnFailedPayment', 'roPaymentsParams'
			);
			echo $form->renderField('confirmationEmail', 'roPaymentsParams');
			echo '<div class="control-group ro-confirmation-info" data-showon=\'[{"field":"roPaymentsParams[confirmationEmail]","values":["1"],"sign":"=","op":""}]\' style="display: none;">';
			echo '<div class="text-info">' . Text::_(
					'PLG_RSFP_JDIDEAL_CONFIRMATIONHELP'
				) . '</div>';
			echo '</div>';
			echo $form->renderField(
				'confirmationRecipient', 'roPaymentsParams'
			);
			echo $form->renderField('confirmationSubject', 'roPaymentsParams');
			echo $form->renderField('confirmationMessage', 'roPaymentsParams');
			echo $form->renderField('includeNonSelected', 'roPaymentsParams');
			echo HTMLHelper::_('bootstrap.endTab');
			echo HTMLHelper::_('bootstrap.endTabSet');
			?>
		</div>
		<?php
	}

	public function rsfp_bk_onAfterShowFormEditTabs(): void
	{
		$this->onRsformBackendAfterShowFormEditTabs();
	}

	/**
	 * Get the component name.
	 *
	 * @param   array  $args  The form argument values
	 *
	 * @return  void
	 *
	 * @since   2.12.0
	 * @throws  RuntimeException
	 */
	public function onRsformBeforeUserEmail(array $args): void
	{
		$form = $args['form'];

		if (!$this->hasRopaymentsFields($form->FormId))
		{
			return;
		}

		$settings = $this->loadFormSettings((int) $form->FormId);
		$total    = RSFormProHelper::componentExists($form->FormId, 5577);

		if (empty($total))
		{
			throw new InvalidArgumentException(
				Text::_('PLG_RSFP_JDIDEAL_TOTAL_FIELD_IS_MISSING')
			);
		}

		$totalDetails = RSFormProHelper::getComponentProperties($total[0]);

		$price = (float) $this->getSubmissionValue(
			$args['submissionId'], $totalDetails['componentId']
		);

		if ($price === 0.00
			&& (int) $settings->get('allowEmpty', 0) === 1)
		{
			return;
		}

		$userEmail = (int) $settings->get('userEmail', 0);

		if ($userEmail === 0)
		{
			return;
		}

		$status  = $this->loadSubmissionValues($args['submissionId']);
		$isValid = $this->isPaymentValid($args);

		if (!isset($status->FieldValue))
		{
			return;
		}

		/**
		 * Defer sending if
		 * - user email is deferred && the payment is not confirmed (send email only when payment is confirmed)
		 */
		if ($userEmail === 1 && (int) $status->FieldValue === 0)
		{
			$args['userEmail']['to'] = '';
		}

		/**
		 * Defer sending if
		 * - user email is not deferred && the payment is confirmed (don't send the email once again, it has already been sent)
		 */
		if ($userEmail === 0 && (int) $status->FieldValue === 1)
		{
			$args['userEmail']['to'] = '';
		}

		/**
		 * Do not send any emails if the payment has been cancelled or failed otherwise
		 */
		if (!$isValid
			&& (int) $settings->get('sendEmailOnFailedPayment', 0) === 0)
		{
			$args['userEmail']['to'] = '';
		}
	}

	public function rsfp_beforeUserEmail(array $args): void
	{
		$this->onRsformBeforeUserEmail($args);
	}

	/**
	 * Load the form details.
	 *
	 * @param   int  $submissionId  The submission ID.
	 *
	 * @return  stdClass
	 *
	 * @since   4.0.0
	 * @throws  RuntimeException
	 */
	private function loadSubmissionValues(int $submissionId): stdClass
	{
		$query = $this->db->getQuery(true)
			->select(
				$this->db->quoteName(
					[
						'FieldValue',
						'FieldName',
					]
				)
			)
			->from($this->db->quoteName('#__rsform_submission_values'))
			->where(
				$this->db->quoteName('FieldName') . ' = ' . $this->db->quote(
					'_STATUS'
				)
			)
			->where(
				$this->db->quoteName('SubmissionId') . ' = ' . $submissionId
			);
		$this->db->setQuery($query);

		try
		{
			$status = $this->db->loadObject();
		}
		catch (Exception $exception)
		{
			$status = new stdClass;
		}

		return $status;
	}

	/**
	 * Check if a payment is valid.
	 *
	 * @param   array  $args  The form arguments.
	 *
	 * @return  boolean  True if payment is valid | False otherwise.
	 *
	 * @since   4.4.0
	 * @throws  Exception
	 */
	private function isPaymentValid(array $args): bool
	{
		$formId = (int) $args['form']->FormId;

		// Get the profile alias from the form
		$profileAlias = $this->getProfileAlias($formId);

		// Load the helper
		$jdideal = new Gateway($profileAlias);

		// Load the payment details
		$details = $jdideal->getDetails(
			$formId . '.' . $args['submissionId'],
			'order_id',
			false,
			'rsformpro'
		);

		// Let's see if there are any details
		if (!is_object($details))
		{
			return false;
		}

		// Return if payment is valid
		return $jdideal->isValid($details->result ?? '');
	}

	/**
	 * Check if we need to defer the email for the administrator.
	 *
	 * @param   array  $args  The form data
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  RuntimeException
	 * @throws  Exception
	 */
	public function onRsformBeforeAdminEmail(array $args): void
	{
		$form = $args['form'];

		if (!$this->hasRopaymentsFields($form->FormId))
		{
			return;
		}

		$settings = $this->loadFormSettings((int) $form->FormId);
		$total    = RSFormProHelper::componentExists($form->FormId, 5577);

		if (empty($total))
		{
			throw new InvalidArgumentException(
				Text::_('PLG_RSFP_JDIDEAL_TOTAL_FIELD_IS_MISSING')
			);
		}

		$totalDetails = RSFormProHelper::getComponentProperties($total[0]);

		$price = (float) $this->getSubmissionValue(
			$args['submissionId'], $totalDetails['componentId']
		);

		if ($price === 0.00
			&& (int) $settings->get('allowEmpty', 0) === 1)
		{
			return;
		}

		$adminEmail = (int) $settings->get('adminEmail', 0);

		if ($adminEmail === 0)
		{
			return;
		}

		$status  = $this->loadSubmissionValues($args['submissionId']);
		$isValid = $this->isPaymentValid($args);

		if (!isset($status->FieldValue))
		{
			return;
		}

		/**
		 * Defer sending if
		 * - admin email is deferred && the payment is not confirmed (send email only when payment is confirmed)
		 */
		if ($adminEmail === 1 && (int) $status->FieldValue === 0)
		{
			$args['adminEmail']['to'] = '';
		}

		/**
		 * Defer sending if
		 * - admin email is not deferred && the payment is confirmed (don't send the email once again, it has already been sent)
		 */
		if ($adminEmail === 0 && (int) $status->FieldValue === 1)
		{
			$args['adminEmail']['to'] = '';
		}

		/**
		 * Do not send any emails if the payment has been cancelled or failed otherwise
		 */
		if (!$isValid && $settings->get('sendEmailOnFailedPayment') === 0)
		{
			$args['adminEmail']['to'] = '';
		}
	}

	public function rsfp_beforeAdminEmail(array $args): void
	{
		$this->onRsformBeforeAdminEmail($args);
	}

	/**
	 * Check if we need to defer the email for the administrator.
	 *
	 * @param   array  $args  The form data
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	public function onRsformBeforeAdditionalEmail(array $args): void
	{
		$form = $args['form'];

		if (!$this->hasRopaymentsFields($form->FormId))
		{
			return;
		}

		$settings = $this->loadFormSettings((int) $form->FormId);
		$total    = RSFormProHelper::componentExists($form->FormId, 5577);

		if (empty($total))
		{
			throw new InvalidArgumentException(
				Text::_('PLG_RSFP_JDIDEAL_TOTAL_FIELD_IS_MISSING')
			);
		}

		$totalDetails = RSFormProHelper::getComponentProperties($total[0]);

		$price = (float) $this->getSubmissionValue(
			$args['submissionId'], $totalDetails['componentId']
		);

		if ($price === 0.00
			&& (int) $settings->get('allowEmpty', 0) === 1)
		{
			return;
		}

		$additionalEmails = (int) $settings->get('additionalEmails', 0);

		if ($additionalEmails === 0)
		{
			return;
		}

		$status  = $this->loadSubmissionValues($args['submissionId']);
		$isValid = $this->isPaymentValid($args);

		if (!isset($status->FieldValue))
		{
			return;
		}

		/**
		 * Defer sending if
		 * - additional email is deferred && the payment is not confirmed (send email only when payment is confirmed)
		 */
		if ($additionalEmails === 1 && (int) $status->FieldValue === 0)
		{
			$args['additionalEmail']['to'] = '';
		}

		/**
		 * Defer sending if
		 * - additional email is not deferred && the payment is confirmed (don't send the email once again, it has already been sent)
		 */
		if ($additionalEmails === 0 && (int) $status->FieldValue === 1)
		{
			$args['additionalEmail']['to'] = '';
		}

		/**
		 * Do not send any emails if the payment has been cancelled or failed otherwise
		 */
		if (!$isValid && $settings->get('sendEmailOnFailedPayment', 0) === 0)
		{
			$args['additionalEmail']['to'] = '';
		}
	}

	public function rsfp_beforeAdditionalEmail(array $args): void
	{
		$this->onRsformBeforeAdditionalEmail($args);
	}

	/**
	 * Delete any form settings on form deletion.
	 *
	 * @param   int  $formId  The ID of the form to delete.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 * @throws  RuntimeException
	 */
	public function onRsformFormDelete(int $formId): void
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__rsform_jdideal'))
			->where($this->db->quoteName('form_id') . ' = ' . $formId);
		$this->db->setQuery($query)->execute();
	}

	public function rsfp_onFormDelete(int $formId): void
	{
		$this->onRsformFormDelete($formId);
	}

	/**
	 * Backup the settings when the user does a form backup.
	 *
	 * @param   object              $form    The form being backed up.
	 * @param   RSFormProBackupXML  $xml     The XML object.
	 * @param   object              $fields  The form fields.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  RuntimeException
	 */
	public function onRsformFormBackup($form, $xml, $fields): void
	{
		$query = $this->db->getQuery(true)
			->select(
				$this->db->quoteName(
					[
						'params',
					]
				)
			)
			->from($this->db->quoteName('#__rsform_jdideal'))
			->where(
				$this->db->quoteName('form_id') . ' = ' . (int) $form->FormId
			);
		$this->db->setQuery($query);

		if ($payment = $this->db->loadObject())
		{
			$xml->add('jdideal');

			foreach ($payment as $property => $value)
			{
				$xml->add($property, $value);
			}

			$xml->add('/jdideal');
		}
	}

	public function rsfp_onFormBackup($form, $xml, $fields): void
	{
		$this->onRsformFormBackup($form, $xml, $fields);
	}

	/**
	 * Restore the settings when the user restores a form from backup.
	 *
	 * @param   object             $form    The form being backed up.
	 * @param   SimpleXMLIterator  $xml     The XML object.
	 * @param   object             $fields  The form fields.
	 *
	 * @return  boolean  True on success | False on failure.
	 *
	 * @since   4.0.0
	 * @throws  RuntimeException
	 */
	public function onRsformFormRestore($form, $xml, $fields): bool
	{
		$tables = $this->db->getTableList();
		$table  = $this->db->getPrefix() . 'rsform_jdideal';

		if (!in_array($table, $tables, true))
		{
			return true;
		}

		if (isset($xml->jdideal->params))
		{
			$query = $this->db->getQuery(true)
				->insert($this->db->quoteName('#__rsform_jdideal'))
				->columns(array('form_id', 'params'))
				->values(
					$form->FormId . ',' . $this->db->quote((string) $xml->jdideal->params)
				);
			$this->db->setQuery($query);

			try
			{
				$this->db->execute();
			}
			catch (Exception $exception)
			{
				return false;
			}
		}

		return true;
	}

	public function rsfp_onFormRestore($form, $xml, $fields): bool
	{
		return $this->onRsformFormRestore($form, $xml, $fields);
	}

	/**
	 * Empty the table when all forms are deleted.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 * @throws  RuntimeException
	 */
	public function onRsformBackendFormRestoreTruncate(): void
	{
		$this->db->truncateTable('#__rsform_jdideal');
	}

	public function rsfp_bk_onFormRestoreTruncate(): void
	{
		$this->onRsformBackendFormRestoreTruncate();
	}

	/**
	 * Load any files needed for the form display.
	 *
	 * @param   array  $details  An array of form details.
	 *
	 * @return  void
	 *
	 * @since   4.3.0
	 */
	public function onRsformBackendBeforeCreateFrontComponentBody(array $details
	): void {
		if ($details['formId'] > 0)
		{
			// Special handling in an article and other extensions
			$extensions = explode(',', $this->params->get('extensions', ''));
			array_unshift($extensions, 'com_content');

			if (!$this->setScript && $this->params->get('forceScript', false))
			{
				$details['out'] .= '<script type="text/javascript" src="' . HTMLHelper::_(
						'script',
						'plg_system_rsfpjdideal/rsfpjdideal.js',
						['version' => 'auto', 'relative' => true, 'pathOnly' => true]
					) . '"></script>';

				$this->setScript = true;
			}

			if (!$this->setScript
				&& in_array(
					$this->app->input->getCmd('option'), $extensions, true
				)
			)
			{
				$jsFile = HTMLHelper::_(
					'script',
					'plg_system_rsfpjdideal/rsfpjdideal.js',
					[
						'version'  => 'auto',
						'pathOnly' => true,
						'relative' => true,
					]
				);

				$document                    = $this->app->getDocument();
				$document->_scripts[$jsFile] = [
					'type'    => 'text/javascript',
					'options' => [
						'version'       => 'auto',
						'relative'      => true,
						'detectDebug'   => 1,
						'detectBrowser' => 1,
						'framework'     => null,
						'pathOnly'      => null,
					],
				];
				$this->setScript             = true;

				return;
			}

			if (!$this->setScript)
			{
				HTMLHelper::_(
					'script',
					'plg_system_rsfpjdideal/rsfpjdideal.js',
					['version' => 'auto', 'relative' => true]
				);

				$this->setScript = true;
			}
		}
	}

	public function rsfp_bk_onBeforeCreateFrontComponentBody(array $details
	): void {
		$this->onRsformBackendBeforeCreateFrontComponentBody($details);
	}

	/**
	 * Copy the settings from the old form to the new form.
	 *
	 * @param   array  $args  The form details.
	 *
	 * @return  void
	 *
	 * @since   4.8.0
	 */
	public function onRsformBackendFormCopy(array $args): void
	{
		$formId    = $args['formId'];
		$newFormId = $args['newFormId'];

		// Get the settings of the current form
		$settings = $this->loadFormSettings($formId);

		// Store the settings in the new form
		$data          = new stdClass;
		$data->form_id = $newFormId;
		$data->params  = json_encode($settings);
		$this->db->insertObject('#__rsform_jdideal', $data);
	}

	public function rsfp_bk_onFormCopy(array $args): void
	{
		$this->onRsformBackendFormCopy($args);
	}

	/**
	 * Add our own submission headers.
	 *
	 * @param   array    $headers  The headers to show
	 * @param   integer  $formId   The form ID the submissions belong to
	 *
	 * @return  void
	 *
	 * @since   4.14
	 */
	public function onRsformBackendGetSubmissionHeaders(&$headers, $formId): void
	{
		if ($this->hasRopaymentsFields($formId))
		{
			$headers[] = Text::_('PLG_RSFP_JDIDEAL_PAYMENT_STATUS');
		}
	}

	public function rsfp_bk_onGetSubmissionHeaders(&$headers, $formId): void
	{
		$this->onRsformBackendGetSubmissionHeaders($headers, $formId);
	}

	/**
	 * Process the submissions by giving a user friendly payment status.
	 *
	 * @param   array  $args  The form submissions.
	 *
	 * @return  void
	 *
	 * @since   4.14
	 */
	public function onRsformBackendManageSubmissions(array $args): void
	{
		foreach ($args['submissions'] as $submissionId => $submission)
		{
			foreach ($submission['SubmissionValues'] as $fieldName => $value)
			{
				if ($fieldName !== '_STATUS')
				{
					continue;
				}

				$args['submissions'][$submissionId]['SubmissionValues'][Text::_(
					'PLG_RSFP_JDIDEAL_PAYMENT_STATUS'
				)]['Value']
					= $value['Value'];
			}
		}
	}

	public function rsfp_b_onManageSubmissions(array $args): void
	{
		$this->onRsformBackendManageSubmissions($args);
	}

	/**
	 * Add the _STATUS field for the front-end directory view.
	 *
	 * @param   array  $fields  The fields to show in the directory list
	 * @param   int    $formId  The form ID being used
	 *
	 * @return  void
	 *
	 * @since   4.16.0
	 */
	public function onRsformBackendGetAllDirectoryFields(&$fields, $formId): void
	{
		if (!$this->hasRopaymentsFields($formId))
		{
			return;
		}

		$field               = new stdClass;
		$field->FieldName    = '_STATUS';
		$field->FieldId      = '-5575';
		$field->FieldType    = 0;
		$field->FieldCaption = Text::_('PLG_RSFP_JDIDEAL_PAYMENT_STATUS');
		$fields[-5575]       = $field;
	}

	public function rsfp_bk_onGetAllDirectoryFields(&$fields, $formId): void
	{
		$this->onRsformBackendGetAllDirectoryFields($fields, $formId);
	}

	/**
	 * Populate the directory listing with the payment status.
	 *
	 * @param   array  $items   List of items shown to be updated
	 * @param   int    $formId  The form ID being used
	 *
	 * @return  void
	 *
	 * @since   4.16.0
	 */
	public function onRsformAfterManageDirectoriesQuery($items, $formId): void
	{
		if (!$this->hasRopaymentsFields($formId))
		{
			return;
		}

		array_walk(
			$items,
			static function (&$item) {
				if (!isset($item->_STATUS))
				{
					return;
				}

				$item->_STATUS = Text::_(
					'PLG_RSFP_JDIDEAL_PAYMENT_STATUS_' . $item->_STATUS
				);
			}
		);
	}

	public function rsfp_onAfterManageDirectoriesQuery($items, $formId): void
	{
		$this->onRsformAfterManageDirectoriesQuery($items, $formId);
	}

	/**
	 * Load the component ID
	 *
	 * @param   int  $formId  The form the component should be used in
	 *
	 * @return  integer The value found in the database
	 *
	 * @since   6.4.0
	 */
	private function getComponentIdChoosePayment(int $formId): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('ComponentId'))
			->from($this->db->quoteName('#__rsform_components'))
			->where($this->db->quoteName('ComponentTypeId') . ' = 27')
			->where($this->db->quoteName('FormId') . ' = ' . $formId);
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}

	/**
	 * Load the component ID for the total field
	 *
	 * @param   int  $formId           The form the component should be used in
	 * @param   int  $componentTypeId  The component type ID
	 *
	 * @return  integer The value found in the database
	 *
	 * @since   6.4.0
	 */
	private function getComponentIdByType(int $formId, int $componentTypeId): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('ComponentId'))
			->from($this->db->quoteName('#__rsform_components'))
			->where($this->db->quoteName('ComponentTypeId') . ' = ' . $componentTypeId)
			->where($this->db->quoteName('FormId') . ' = ' . $formId);
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
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

	/**
	 * Create custom placeholders for specific fields.
	 *
	 * @param   array  $placeholders  The array of placeholders to extend
	 * @param   int    $componentId   The component ID being processed
	 *
	 * @return  array  The list of placeholders
	 *
	 * @since   7.0.0
	 */
	public function onRsformAfterCreateQuickAddPlaceholders(&$placeholders, $componentId)
	{
		if (!in_array($componentId, $this->ropaymentsFields))
		{
			return $placeholders;
		}

		switch ($componentId)
		{
			case ROPAYMENTS_FIELD_PAYMENT_SINGLE_PRODUCT:
			case ROPAYMENTS_FIELD_PAYMENT_TOTAL:
			case ROPAYMENTS_FIELD_PAYMENT_INPUT:
			case ROPAYMENTS_FIELD_PAYMENT_MULTIPLE_PRODUCTS:
				$placeholders['display'][] = '{' . $placeholders['name'] . ':price}';
				$placeholders['display'][] = '{' . $placeholders['name'] . ':amount}';
				break;

			case ROPAYMENTS_FIELD_PAYMENT_CHOOSE:
				$placeholders['display'][] = '{' . $placeholders['name'] . ':text}';
				$placeholders['display'][] = '{_STATUS:caption}';
				$placeholders['display'][] = '{_STATUS:value}';
				$placeholders['display'][] = '{_TRANSACTION_ID:caption}';
				$placeholders['display'][] = '{_TRANSACTION_ID:value}';
				break;

			case ROPAYMENTS_FIELD_PAYMENT_DISCOUNT:
				$placeholders['display'][] = '{discount}';
				$placeholders['display'][] = '{discountprice}';
				break;
		}
	}

	/**
	 * Validate the form fields.
	 *
	 * @param   array  $args  The invalid, form ID and post values
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	public function onRsformFrontendBeforeFormValidation(array $args): void
	{
		if (!array_key_exists('ropayments', $args['post']))
		{
			return;
		}

		foreach ($args['post']['ropayments']['quantity'] as $fieldName => $values)
		{
			$componentId = $this->getComponentId($fieldName, (int) $args['formId']);

			if (!isset($args['post'][$fieldName]))
			{
				$args['invalid'][] = $componentId;
				continue;
			}

			$selected = false;
			$required = $this->getPropertyValue($componentId, 'REQUIRED');

			if (strtolower($required) === 'no')
			{
				continue;
			}

			$minimum = $this->getPropertyValue($componentId, 'BOXMIN');
			$maximum = $this->getPropertyValue($componentId, 'BOXMAX');

			foreach ($values as $value)
			{
				if ($value > 0
					&& $value >= $minimum
					&& $value <= $maximum
				)
				{
					$selected = true;
				}
			}

			if (!$selected)
			{
				$args['invalid'][] = $componentId;
			}
		}
	}
}
