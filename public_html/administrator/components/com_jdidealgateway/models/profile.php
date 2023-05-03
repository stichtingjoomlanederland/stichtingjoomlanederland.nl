<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

defined('_JEXEC') or die;

/**
 * Profile model.
 *
 * @package  JDiDEAL
 * @since    4.0.0
 */
class JdidealgatewayModelProfile extends AdminModel
{
	/**
	 * Form context
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $context = 'com_jdidealgateway.profile';

	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success | False on failure.
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 *
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_jdidealgateway.profile',
			'profile',
			['control' => 'jform', 'load_data' => $loadData]
		);

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   string  $provider  The name of the payment provider to load the form for.
	 *
	 * @return  mixed  A JForm object on success, false on failure.
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	public function getPspForm(string $provider)
	{
		$form = $this->loadForm(
			$this->context . '.' . $provider,
			$provider,
			['control' => 'jform', 'load_data' => false]
		);

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Check if the security files exist.
	 *
	 * @return  array  Array with results of file check.
	 *
	 * @since   4.0.0
	 */
	public function getFilesExist(): array
	{
		$filesExists         = [];
		$certificatePath     = JPATH_LIBRARIES . '/Jdideal/Psp/Advanced/certificates';
		$filesExists['cert'] = File::exists($certificatePath . '/cert.cer');
		$filesExists['priv'] = File::exists($certificatePath . '/priv.pem');

		return $filesExists;
	}

	/**
	 * Save the configuration.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success or false on failure.
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	public function save($data): bool
	{
		$app = Factory::getApplication();

		// Get the PSP form data
		$formData = $app->input->post->get('jform', [], 'array');
		$data     = array_merge($data, $formData);

		// Trim text fields
		$trimFields = [
			'merchant_id',
			'shainkey',
			'shaoutkey',
			'hash',
			'merchantId',
			'IDEAL_PrivatekeyPass',
			'IDEAL_MerchantID',
			'IDEAL_SubID',
			'secret_key',
			'merchant_key',
			'sharedSecret',
			'hashkey',
			'subId',
			'apiKey',
			'partner_id',
			'profile_key',
			'signingKey',
			'apiKey',
			'password',
			'keyversion',
			'merchant_key',
			'shop_id',
			'rtlo',
		];

		foreach ($trimFields as $trimField)
		{
			if (array_key_exists($trimField, $formData))
			{
				$formData[$trimField] = trim($formData[$trimField]);
			}
		}

		// Store the settings as a JSON string
		$params              = new Registry($formData);
		$data['paymentInfo'] = $params->toString();

		// Make sure the COMPLUS field is selected for Internetkassa
		if ($data['psp'] === 'ogone' || $data['psp'] === 'abn-internetkassa')
		{
			if (!in_array('COMPLUS', $data['dynamic_parameters'], true))
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_ROPAYMENTS_MISSING_COMPLUS_PARAMETER'), 'error');

				return false;
			}
		}

		// Clean the iDEAL Advanced description
		if ($data['psp'] === 'advanced' || $data['psp'] === 'ing')
		{
			$data['IDEAL_DESCRIPTION'] = str_ireplace(array('&'), '', $data['IDEAL_DESCRIPTION']);
		}

		// Alter the title for save as copy
		if ($app->input->get('task') === 'save2copy')
		{
			$origTable = clone $this->getTable();
			$origTable->load($app->input->getInt('id'));

			if ($data['name'] === $origTable->get('name'))
			{
				[$title, $alias] = $this->generateNewTitle(null, $data['alias'], $data['name']);
				$data['name']  = $title;
				$data['alias'] = $alias;
			}
			else
			{
				if ($data['alias'] == $origTable->get('alias'))
				{
					$data['alias'] = '';
				}
			}

			// Set the new ordering value
			$data['ordering'] = $origTable->get('ordering') + 1;

			// Unset the ID so a new item is created
			unset($data['id']);
		}

		// Save the profile
		if (!parent::save($data))
		{
			return false;
		}

		// Check if any security files are uploaded
		$files = $app->input->files->get('jform');

		if ($files)
		{
			foreach ($files as $names)
			{
				foreach ($names as $name => $info)
				{
					$cert = false;

					switch ($name)
					{
						case 'cert_upload':
							if ($info['error'] === 0)
							{
								$cert                = true;
								$data['cert_upload'] = $info['name'];
							}
							break;
						case 'priv_upload':
							if ($info['error'] === 0)
							{
								// Check if the filename is correct
								$cert                = true;
								$data['priv_upload'] = $info['name'];
							}
							break;
					}

					if ($cert)
					{
						$folder = JPATH_LIBRARIES . '/Jdideal/Psp/' . ucfirst($data['psp']) . '/certificates';

						if (File::upload($info['tmp_name'], $folder . '/' . $info['name']))
						{
							$app->enqueueMessage(Text::_('COM_ROPAYMENTS_NAME_CERT_UPLOADED'));
						}
					}
				}
			}
		}

		if ($data['psp'] === 'ing')
		{
			$this->storeIngConfig($data);
		}

		return true;
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $categoryId  The id of the category.
	 * @param   string   $alias       The alias.
	 * @param   string   $title       The title.
	 *
	 * @return  array  Contains the modified title and alias.
	 *
	 * @since   4.5.0
	 * @throws  Exception
	 */
	protected function generateNewTitle($categoryId, $alias, $title): array
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('alias' => $alias)))
		{
			$title = StringHelper::increment($title);
			$alias = StringHelper::increment($alias, 'dash');
		}

		return array($title, $alias);
	}

	/**
	 * Method to get the data that should be injected in the form..
	 *
	 * @return  array  The data for the form.
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jdidealgateway.edit.profile.data', []);

		if (0 === count($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	public function getItem($pk = null)
	{
		$item     = parent::getItem($pk);
		$forcePsp = Factory::getApplication()->getUserState('profile.psp', false);

		if ($forcePsp)
		{
			$item->psp = $forcePsp;
		}

		// Get the payment information
		$settings          = new Registry($item->paymentInfo);
		$item->paymentInfo = $settings->toObject();

		// Get the email fields
		$item->status_mismatch        = $settings->get('status_mismatch');
		$item->admin_order_payment    = $settings->get('admin_order_payment');
		$item->admin_status_failed    = $settings->get('admin_status_failed');
		$item->inform_email           = $settings->get('inform_email');
		$item->jdidealgateway_emailto = $settings->get('jdidealgateway_emailto');
		$item->customer_change_status = $settings->get('customer_change_status');

		return $item;
	}

	/**
	 * Store the ING configuration in a conf file.
	 *
	 * @param   array  $data
	 *
	 * @return  void
	 *
	 * @since   8.0.0
	 * @throws  Exception
	 */
	private function storeIngConfig(array $data): void
	{
		$file      = JPATH_LIBRARIES . '/Jdideal/Psp/Ing/Connector/config.conf';
		$oldConfig = parse_ini_file($file);
		$params    = ComponentHelper::getParams('com_jdidealgateway');
		$domain    = $params->get('domain');

		if (substr($domain, -1) === '/')
		{
			$domain = substr($domain, 0, -1);
		}

		if (empty($domain) || strpos($domain, 'http') === false)
		{
			throw new InvalidArgumentException(Text::_('COM_ROPAYMENTS_DOMAIN_NAME_NOT_SET'));
		}

		$content = 'MERCHANTID=' . trim($data['IDEAL_MerchantID']) . PHP_EOL;
		$content .= 'SUBID=' . $data['IDEAL_SubID'] . PHP_EOL;
		$content .= 'MERCHANTRETURNURL=' . $domain . '/cli/notify.php' . PHP_EOL;

		$acquirerUrl = 'https://sandbox.ideal-acquiring.ing.nl/ideal/iDEALv3';

		if ($data['IDEAL_Bank'] === 'INGSEPA')
		{
			$acquirerUrl = 'https://ideal-acquiring.ing.nl/ideal/iDEALv3';
		}

		$content .= 'ACQUIRERURL=' . $acquirerUrl . PHP_EOL;
		$content .= 'ACQUIRERTIMEOUT=10' . PHP_EOL;
		$content .= 'EXPIRATIONPERIOD=PT1H' . PHP_EOL;

		$privateCert = $oldConfig['PRIVATECERT'];

		if (isset($data['cert_upload']))
		{
			$privateCert = JPATH_LIBRARIES . '/Jdideal/Psp/Ing/certificates/' . $data['cert_upload'];
		}

		$content .= 'PRIVATECERT=' . $privateCert . PHP_EOL;
		$content .= 'PRIVATEKEYPASS=' . $data['IDEAL_PrivatekeyPass'] . PHP_EOL;

		$privateKey = $oldConfig['PRIVATEKEY'];

		if (isset($data['priv_upload']))
		{
			$privateKey = JPATH_LIBRARIES . '/Jdideal/Psp/Ing/certificates/' . $data['priv_upload'];
		}

		$content .= 'PRIVATEKEY=' . $privateKey . PHP_EOL;

		$ingCertificate = 'sandbox_ideal_v3.cer';

		if ($data['IDEAL_Bank'] === 'INGSEPA')
		{
			$ingCertificate = 'ideal_v3.cer';
		}

		$content .= 'CERTIFICATE0=' . JPATH_LIBRARIES . '/Jdideal/Psp/Ing/certificates/' . $ingCertificate . PHP_EOL;
		$content .= 'LOGFILE=' . JPATH_ADMINISTRATOR . '/logs' . PHP_EOL;

		$traceLevel = '';

		if (Factory::getApplication()->get('debug', false))
		{
			$traceLevel = 'DEBUG';
		}

		$content .= 'TRACELEVEL=' . $traceLevel . PHP_EOL;

		if (file_put_contents($file, $content) === false)
		{
			throw new RuntimeException(Text::sprintf('COM_ROPAYMENTS_CANNOT_WRITE_ING_CONFIG_FILE', $file));
		}
	}
}
