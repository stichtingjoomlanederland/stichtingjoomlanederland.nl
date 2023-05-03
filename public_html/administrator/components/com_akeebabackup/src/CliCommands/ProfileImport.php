<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ConfigureIO;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\InitialiseEngine;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\PrintFormattedArray;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:profile:import
 *
 * Imports an Akeeba Backup profile from a JSON string.
 *
 * @since   7.5.0
 */
class ProfileImport extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:profile:import';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   7.5.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureSymfonyIO($input, $output);

		try
		{
			$this->initialiseComponent($this->getApplication());
		}
		catch (\Throwable $e)
		{
			$this->ioStyle->error([
				Text::_('COM_AKEEBABACKUP_CLI_ERR_CANNOT_LOAD_BACKUP_ENGINGE'),
				$e->getMessage(),
			]);

			return 255;
		}

		$filename = (string) $this->cliInput->getArgument('fileOrJSON') ?? '';
		$json     = $this->getJSON($filename);

		try
		{
			$decoded = @json_decode($json, true);
		}
		catch (Exception $e)
		{
			$decoded = '';
		}

		if (empty($decoded))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_ERR_INVALID_JSON'));

			return 1;
		}

		// We must never pass an ID, forcing the model to create a new record
		if (isset($decoded['id']))
		{
			unset($decoded['id']);
		}

		/** @var ProfileModel $model */
		$model = $this->getMVCFactory()->createModel('Profile', 'Administrator');
		$table = $model->getTable();

		if (!$table->save($decoded))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_ERR_GENERIC', $table->getError()));

			return 2;
		}

		$id     = $table->getId();
		$format = (string) $this->cliInput->getOption('format') ?? 'text';

		if ($format == 'json')
		{
			echo json_encode($id);

			return 0;
		}

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_LBL_SUCCESS', $id));

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   7.5.0
	 */
	protected function configure(): void
	{
		$this->addArgument('fileOrJSON', InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_OPT_FILEORJSON'));
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_OPT_FORMAT'), 'text');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_HELP'));
	}

	/**
	 * Get the JSON input
	 *
	 * @param   string|null  $filename  The filename to read from, raw JSON data or an empty string
	 *
	 * @return  string  The JSON data
	 *
	 * @since   7.5.0
	 */
	private function getJSON(?string $filename): string
	{
		// No filename or JSON string passed to script; use STDIN
		if (empty($filename))
		{
			$json = '';

			while (!feof(STDIN))
			{
				$json .= fgets(STDIN) . "\n";
			}

			return rtrim($json);
		}

		// An existing file path was passed. Return the contents of the file.
		if (@file_exists($filename))
		{
			$ret = @file_get_contents($filename);

			if ($ret === false)
			{
				return '';
			}
		}

		// Otherwise assume raw JSON was passed back to us.
		return $filename;
	}

}
