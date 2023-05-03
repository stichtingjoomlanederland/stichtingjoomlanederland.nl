<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Includefolders;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\IncludefoldersModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewProfileIdAndNameTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;

	public function onBeforeMain()
	{
		$this->document->getWebAssetManager()
			->useScript('com_akeebabackup.includefolders');

		$this->addToolbar();

		// Enable Bootstrap popovers
		HTMLHelper::_('bootstrap.popover', '[rel=popover]', [
			'html'      => true,
			'placement' => 'bottom',
			'trigger'   => 'click hover',
			'sanitize'  => false,
		]);

		// Get a JSON representation of the directories data
		/** @var IncludefoldersModel $model */
		$model = $this->getModel();

		$this->document
			->addScriptOptions('akeebabackup.System.params.AjaxURL', Route::_('index.php?option=com_akeebabackup&view=Includefolders&task=ajax', false, Route::TLS_IGNORE, true))
			->addScriptOptions('akeebabackup.Configuration.URLs', [
				'browser' => Route::_('index.php?option=com_akeebabackup&view=Browser&processfolder=1&tmpl=component&folder=', false, Route::TLS_IGNORE, true),
			])
			->addScriptOptions('akeebabackup.Includefolders.guiData', $model->get_directories());

		$this->getProfileIdAndName();

		// Push translations
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIERRORFILTER');
	}

	private function addToolbar(): void
	{
		$toolbar = Toolbar::getInstance();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (\Joomla\CMS\Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/off-site-directories-inclusion.html');
	}

}