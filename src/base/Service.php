<?php

namespace Masuga\CpFilters\base;

use Craft;
use craft\elements\Entry;
use craft\helpers\FileHelper;
use Masuga\CpFilters\CpFilters;
use yii\base\Component;

/**
 * The CP Filter's base service class. All plugin services should extend this one.
 * @author Masuga Design
 */
abstract class Service extends Component
{
	/**
	 * The instance of the CpFilters class.
	 * @var CpFilters
	 */
	protected $plugin = null;

	/**
	 * The full system path to the plugin service log.
	 * @var string
	 */
	protected $logPath = null;

	/**
	 * We'll keep a single boolean instance of whether or not Craft is in devMode
	 * to avoid having to load the config numerous times throughout a single request.
	 * @var bool
	 */
	protected $craftDevMode = false;

	/**
	 * Craft calls the init() method when initializing the plugin components.
	 */
	public function init()
	{
		$this->plugin = CpFilters::getInstance();
		$this->logPath = Craft::$app->getPath()->getLogPath().'/cpfilters.log';
		$this->craftDevMode = Craft::$app->getConfig()->getGeneral()->devMode;
	}

	/**
	 * This method attempts to fetch a single entry given a supplied array of criteria.
	 * @param array $criteria
	 * @return Entry|null
	 */
	public function fetchEntryByCriteria($criteria)
	{
		$query = Entry::find();
		Craft::configure($query, $criteria);
		$entry = $query->anyStatus()->one();
		return $entry;
	}

	/**
	 * This method fetches ALL entries that match given criteria. Be very careful
	 * with this because no limits are imposed. You may run out of memory.
	 * @param array $criteria
	 * @param bool $asArray
	 * @return array
	 */
	public function fetchEntriesByCriteria($criteria, $asArray=false): array
	{
		$query = Entry::find();
		Craft::configure($query, $criteria);
		if ( $asArray ) {
			$query->asArray();
		}
		return $query->limit(null)->all();
	}

	/**
	 * This method logs information to the plugin service log.
	 * @param mixed $content
	 * @param bool $devModeOnly
	 */
	public function log($content, $devModeOnly=false)
 	{
 		if ( ! $devModeOnly || $this->craftDevMode ) {
 			$timestamp = '['.date('Y-m-d g:i a').'] :: ';
 			FileHelper::writeToFile($this->logPath, $timestamp.$content.PHP_EOL, [
 				'append' => true,
 				'lock' => false // Will this prevent permission issues?
 			]);
 		}
 	}
}
