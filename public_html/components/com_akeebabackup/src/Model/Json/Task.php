<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json;

// Protect from unauthorized access
use DirectoryIterator;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use RuntimeException;

defined('_JEXEC') || die();

/**
 * Handles task execution
 */
class Task
{
	/** @var  TaskInterface[]  The task handlers known to us */
	protected $handlers = array();

	/**
	 * The MVC Factory of the extension.
	 *
	 * This is used by tasks to instantiate the various Models we need.
	 *
	 * @var MVCFactoryInterface
	 */
	protected $MVCFactory;

	/**
	 * Public constructor. Populates the list of task handlers.
	 *
	 * @param   MVCFactoryInterface  $MVCFactory
	 */
	public function __construct(MVCFactoryInterface $MVCFactory)
	{
		$this->MVCFactory = $MVCFactory;

		// Populate the list of task handlers
		$this->initialiseHandlers();
	}

	/**
	 * Do I have a specific task handling method?
	 *
	 * @param   string  $method  The method to check for
	 *
	 * @return  bool
	 */
	public function hasMethod($method)
	{
		$method = strtolower($method);

		return isset($this->handlers[$method]);
	}

	/**
	 * Execute a JSON API method
	 *
	 * @param   string  $method      The method's name
	 * @param   array   $parameters  The parameters to the method (optional)
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException  When the method requested is not known to us
	 */
	public function execute($method, $parameters = array())
	{
		if ((!defined('AKEEBABACKUP_PRO') || !AKEEBABACKUP_PRO) && (time() >= 1583020800))
		{
			throw new RuntimeException('Access denied', 503);
		}

		if (!$this->hasMethod($method))
		{
			throw new RuntimeException("Invalid method $method", 405);
		}

		$method = strtolower($method);

		return $this->handlers[$method]->execute($parameters);
	}

	/**
	 * Initialises the encapsulation handlers
	 *
	 * @return  void
	 */
	protected function initialiseHandlers()
	{
		// Reset the array
		$this->handlers = array();

		// Look all files in the Task handlers' directory
		$dh = new DirectoryIterator(__DIR__ . '/Task');

		/** @var DirectoryIterator $entry */
		foreach ($dh as $entry)
		{
			$fileName = $entry->getFilename();

			// Ignore non-PHP files
			if (substr($fileName, -4) != '.php')
			{
				continue;
			}

			// Ignore the Base class
			if ($fileName == 'AbstractTask.php')
			{
				continue;
			}

			// Get the class name
			$className = __NAMESPACE__ . '\\Task\\' . substr($fileName, 0, -4);

			// Check if the class really exists
			if (!class_exists($className, true))
			{
				continue;
			}

			/** @var TaskInterface $o */
			$o = new $className($this->MVCFactory);
			$name = $o->getMethodName();
			$name = strtolower($name);
			$this->handlers[$name] = $o;
		}
	}

}
