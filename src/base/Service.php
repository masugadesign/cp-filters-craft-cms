<?php

namespace Masuga\CpFilters\base;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\helpers\FileHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\exceptions\InvalidElementTypeException;
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
	 * An associative array of element type keys to their respective element type
	 * class name.
	 * @var array
	 */
	const ELEMENT_TYPES = [
		'assets' => 'craft\elements\Asset',
		'categories' => 'craft\elements\Category',
		'entries' => 'craft\elements\Entry',
		'tags' => 'craft\elements\Tag',
		'users' => 'craft\elements\User',
	];

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
	 * This method returns the full class name of an element based on its type
	 * key.
	 * @param string $typeKey
	 * @return string
	 */
	public function getElementClass($typeKey): string
	{
		return self::ELEMENT_TYPES[$typeKey] ?? '';
	}

	/**
	 * This method constructs an ElementQuery for a given class and array of
	 * criteria.
	 * @param string $class
	 * @param array $criteria
	 * @return Query
	 */
	public function elementQuery($class, $criteria): Query
	{
		if ( ! is_subclass_of($class, Element::class) ) {
			throw new InvalidElementTypeException();
		}
		$query = $class::find();
		Craft::configure($query, $criteria);
		return $query;
	}

	/**
	 * This method fetches elements of a particular type based on the provided
	 * criteria.
	 * @param string $typeKey
	 * @param array $criteria
	 * @param bool $asArray
	 * @return array
	 * @throws InvalidElementTypeException
	 */
	public function fetchElementsByCriteria($typeKey, $criteria, $asArray=false): array
	{
		$type = self::ELEMENT_TYPES[$typeKey] ?? null;
		if ( ! is_subclass_of($elementType, Element::class) ) {
			throw new InvalidElementTypeException();
		}
		$query = $type::find();
		Craft::configure($query, $criteria);
		if ( $asArray ) {
			$query->asArray();
		}
		return $query->limit(null)->all();
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
