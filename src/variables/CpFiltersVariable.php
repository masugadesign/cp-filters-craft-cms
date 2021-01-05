<?php

namespace Masuga\CpFilters\variables;

use Craft;
use craft\elements\db\ElementQuery;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\elements\db\SavedFilterQuery;
use yii\helpers\Inflector;

class CpFiltersVariable
{

	/**
	 * The instance of the CpFilters plugin class.
	 * @var CpFilters
	 */
	private $plugin = null;

	public function __construct()
	{
		$this->plugin = CpFilters::getInstance();
	}

	/**
	 * This method returns the full class name of an element based on its type
	 * key.
	 * @param string $typeKey
	 * @return string
	 */
	public function getElementClass($typeKey): string
	{
		return $this->plugin->filters->getElementClass($typeKey);
	}

	/**
	 * This method determines whether we should key on `typeId`, `groupId`, or
	 * `volumeId`.
	 * @param string $typeKey
	 * @return string
	 */
	public function groupParamKey($typeKey): string
	{
		return $this->plugin->filters->groupParamKey($typeKey);
	}

	/**
	 * This method returns the available options for a specified element type
	 * based on its CP Filters key value.
	 * @param string $typeKey
	 * @return array
	 */
	public function groupOptions($typeKey): array
	{
		return $this->plugin->filters->groupOptions($typeKey);
	}

	/**
	 * This method fetches an array of fields belonging to a field layout that
	 * belongs to an element grouping of some sort (entry type, volume, group).
	 * @param string $typeKey
	 * @param int $groupId
	 * @return array
	 */
	public function elementGroupFields($typeKey, $groupId): array
	{
		return $this->plugin->filters->elementGroupFields($typeKey, $groupId);
	}

	public function elementQuery($class, $criteria): ElementQuery
	{
		return $this->plugin->filters->elementQuery($class, $criteria);
	}

	/**
	 * This method fetches elements of a particular type based on the provided
	 * criteria.
	 * @param string $typeKey
	 * @param array $criteria
	 * @param bool $asArray
	 * @return array
	 */
	public function fetchElementsByCriteria($typeKey, $criteria, $asArray=false): array
	{
		return $this->plugins->filters->fetchElementsByCriteria($typeKey, $criteria, $asArray);
	}

	/**
	 * This method converts an array of filters form inputs into Craft entry
	 * query criteria.
	 * @param array $criteria
	 * @return array
	 */
	public function formatFilterCriteria($criteria): array
	{
		return $this->plugin->filters->formatCriteria($criteria);
	}

	/**
	 * This method fetches an array of fields belonging to a field layout that
	 * belongs to an entry type.
	 * @param int $entryTypeId
	 * @return array
	 */
	public function assetVolumeFields($volumeId): array
	{
		return $this->plugin->assetVolumes->fields($entryTypeId);
	}

	/**
	 * This method fetches an array of fields belonging to a field layout that
	 * belongs to an entry type.
	 * @param int $entryTypeId
	 * @return array
	 */
	public function entryTypeFields($entryTypeId): array
	{
		return $this->plugin->entryTypes->fields($entryTypeId);
	}

	/**
	 * This method returns the list of excluded native fields by element type key.
	 * @param string $typeKey
	 * @return array
	 */
	public function excludedFields($typeKey): array
	{
		return $this->plugin->fieldTypes->excludedFields($typeKey);
	}

	/**
	 * This method fetches an array of native field handles to labels.
	 * @param string $typeKey
	 * @return array
	 */
	public function nativeFieldsByType($typeKey)
	{
		$excludes = $this->plugin->fieldTypes->excludedFields($typeKey);
		return $this->plugin->fieldTypes->nativeFields($excludes);
	}

	/**
	 * This variable converts a camelCase string to words.
	 * @param string
	 * @return string
	 */
	public function toWords($camelCase)
	{
		return Inflector::camel2words($camelCase);
	}

	/**
	 * This method creates a string preview of a field value. This representation
	 * may vary based on field type.
	 * @param Entry $entry
	 * @param string $fieldHandle
	 * @return string
	 */
	public function previewValue($entry, $fieldHandle): string
	{
		return $this->plugin->fieldTypes->previewValue($entry, $fieldHandle);
	}

	/**
	 * This method fetches the array of filter types based on a supplied field
	 * handle. It also allows for an optional selected value parameter.
	 * @param string $fieldHandle
	 * @param string $selected
	 * @return string
	 */
	public function fieldFilterOptions($fieldHandle, $selected=null): string
	{
		return $this->plugin->fieldTypes->getFilterOptionsByFieldHandle($fieldHandle, true, $selected);
	}

	/**
	 * This template variable returns an associative array of field option values
	 * and labels for a given field handle. Though it is easy to do this natively
	 * with Craft, I wanted a consistent behavior so that an empty array is returned
	 * for methods without options.
	 * @param string $fieldHandle
	 * @return array
	 */
	public function fieldOptions($fieldHandle): array
	{
		return $this->plugin->fieldTypes->getFieldOptionsByFieldHandle($fieldHandle);
	}

	/**
	 * This method returns an array of status options by element type key.
	 * @param string $typeKey
	 * @return array
	 */
	public function statusOptions($typeKey): array
	{
		return $this->plugin->filters->statusOptionsByTypeKey($typeKey);
	}

	/**
	 * This method determines the appropriate element group criteria.
	 * @param string $elementTypeKey
	 * @param mixed $groupId
	 * @return array|string
	 */
	public function elementGroupCriteria($elementTypeKey, $groupId)
	{
		return $this->plugin->filters->elementGroupCriteria($elementTypeKey, $groupId);
	}

	/**
	 * This method fetches a user's Saved Filters.
	 * @param int $userId
	 * @return array
	 */
	public function savedFilters($criteria=[]): SavedFilterQuery
	{
		return $this->plugin->savedFilters->savedFilters($criteria);
	}

	/**
	 * This method takes a saved filter's information and returns a url to
	 * use to show the results of that filter.
	 * @param array $element
	 * @return array
	 */
	public function createFilterUrl($element)
	{
		return $this->plugin->savedFilters->createFilterUrl($element);
	}

}
