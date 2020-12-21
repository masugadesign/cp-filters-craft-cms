<?php

namespace Masuga\CpFilters\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\elements\SavedFilter;

/**
 */
class SavedFilterQuery extends ElementQuery
{
	/**
	 * Public properties
	 */
	public $userId = null;
	public $title = null;
	public $filterElementType = null;
	public $filterGroupId = null;
	public $filterCriteria = null;

	/**
	 * The instance of the CP Filters plugin.
	 * @var CPFilters
	 */
	private $plugin = null;

	/**
	* @inheritdoc
	*/
	protected $defaultOrderBy = ['cpfilters_savedfilters.dateCreated' => SORT_DESC];

	/**
	 * Override the established __set() method so we can add properties on-the-fly
	 * from the init() method.
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->{$name} = $value;
	}

	/**
	 * Override the established __get() method so we can get properties on-the-fly
	 * from the init() method.
	 */
	public function __call($name, $args)
	{
		if ( ! method_exists($this, $name) && property_exists($this, $name) && count($args) === 1 ) {
			$this->$name = $args[0];
			return $this;
		}
	}

	public function init()
	{
		parent::init();
		$this->plugin = CpFilters::getInstance();
	}

	/**
	 * @inheritdoc
	 */
	public function with($value)
	{
		$this->with = $value;
		return $this;
	}

	public function userId($value)
	{
		$this->userId = $value;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare(): bool
	{
		$this->joinElementTable('cpfilters_savedfilters');

		$selectsArray = [
			'cpfilters_savedfilters.userId',
			'cpfilters_savedfilters.title',
			'cpfilters_savedfilters.filterElementType',
			'cpfilters_savedfilters.filterGroupId',
			'cpfilters_savedfilters.filterCriteria'
		];
		$this->query->select($selectsArray);

		if ($this->userId) {
			$this->subQuery->andWhere(Db::parseParam('cpfilters_savedfilters.userId', $this->userId));
		}
		if ($this->title) {
			$this->subQuery->andWhere(Db::parseParam('cpfilters_savedfilters.title', $this->userId));
		}
		if ($this->filterElementType) {
			$this->subQuery->andWhere(Db::parseParam('cpfilters_savedfilters.filterElementType', $this->filterElementType));
		}
		if ($this->filterGroupId) {
			$this->subQuery->andWhere(Db::parseParam('cpfilters_savedfilters.filterGroupId', $this->filterGroupId));
		}
		if ($this->filterCriteria) {
			$this->subQuery->andWhere(Db::parseParam('cpfilters_savedfilters.filterCriteria', $this->filterCriteria));
		}
		return parent::beforePrepare();
	}
}