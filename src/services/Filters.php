<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use Masuga\CpFilters\base\Service;

class Filters extends Service
{

	/**
	 * This method determines whether we should key on `typeId`, `groupId`, or
	 * `volumeId`.
	 * @param string $typeKey
	 * @return string
	 */
	public function groupParamKey($typeKey): string
	{
		if ( $typeKey === 'entries' ) {
			$key = 'typeId';
		} elseif ( $typeKey === 'assets' ) {
			$key = 'volumeId';
		} else {
			$key = 'groupId';
		}
		return $key;
	}

	/**
	 * This method returns the available options for a specified element type
	 * based on its CP Filters key value.
	 * @param string $typeKey
	 * @return array
	 */
	public function groupOptions($typeKey): array
	{
		$options = [];
		if ( $typeKey === 'entries' ) {
			$options = $this->plugin->entryTypes->groupOptions();
		} elseif ( $typeKey === 'assets' ) {
			$options = $this->plugin->assetVolumes->groupOptions();
		} elseif ( $typeKey === 'users' ) {
			$options = []; //$this->plugin->userGroups->groupOptions();
		} elseif ( $typeKey === 'categories' ) {
			$options = $this->plugin->categoryGroups->groupOptions();
		} elseif ( $typeKey === 'tags') {
			$options = $this->plugin->tagGroups->groupOptions();
		}
		return $options;
	}

	/**
	 * This method returns group/type IDs for allowed filterables groups/types.
	 * @param string $typeKey
	 * @return array|string
	 */
	public function groupOptionIds($typeKey)
	{
		$ids = [];
		if ( $typeKey === 'entries' ) {
			$ids = $this->plugin->getSettings()->filterableEntryTypeIds;
		} elseif ( $typeKey === 'assets' ) {
			$ids = $this->plugin->getSettings()->filterableAssetVolumeIds;
		} elseif ( $typeKey === 'users' ) {
			$ids = '*';
		} elseif ( $typeKey === 'categories' ) {
			$ids = $this->plugin->getSettings()->filterableCategoryGroupIds;
		} elseif ( $typeKey === 'tags') {
			$ids = $this->plugin->getSettings()->filterableTagGroupIds;
		}
		return $ids;
	}

	/**
	 * This method determines the appropriate element group criteria.
	 * @param string $elementTypeKey
	 * @param mixed $groupId
	 * @return array|string
	 */
	public function elementGroupCriteria($typeKey, $groupId=null)
	{
		$criteria = [];
		$fallBackIds = $this->plugin->filters->groupOptionIds($typeKey);
		if ( $typeKey === 'assets' ) {
			$criteria['volumeId'] = $groupId ?: $fallBackIds;
		} elseif ( $typeKey === 'categories' ) {
			$criteria['groupId'] = $groupId ?: $fallBackIds;
		} elseif ( $typeKey === 'entries' ) {
			$criteria['typeId'] = $groupId ?: $fallBackIds;
		} elseif ( $typeKey === 'tags' ) {
			$criteria['groupId'] = $groupId ?: $fallBackIds;
		}
		return $criteria;
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
		$fields = [];
		if ( $typeKey === 'entries' ) {
			$fields = $groupId ? $this->plugin->entryTypes->fields($groupId) : [];
		} elseif ( $typeKey === 'assets' ) {
			$fields = $groupId ? $this->plugin->assetVolumes->fields($groupId) : [];
		} elseif ( $typeKey === 'users' ) {
			$fields = $this->plugin->userGroups->fields();
		} elseif ( $typeKey === 'categories' ) {
			$fields = $groupId ? $this->plugin->categoryGroups->fields($groupId) : [];
		} elseif ( $typeKey === 'tags') {
			$fields = $groupId ? $this->plugin->tagGroups->fields($groupId) : [];
		}
		return $fields;
	}

	/**
	 * This method returns an array of status options by element type key.
	 * @param string $typeKey
	 * @return array
	 */
	public function statusOptionsByTypeKey($typeKey): array
	{
		$options = [];
		if ( $typeKey === 'entries' ) {
			$options = $this->plugin->entryTypes->statusOptions();
		} elseif ( $typeKey === 'assets' ) {
			$options = $this->plugin->assetVolumes->statusOptions();
		} elseif ( $typeKey === 'users' ) {
			$options = $this->plugin->userGroups->statusOptions();
		} elseif ( $typeKey === 'categories' ) {
			$options = $this->plugin->categoryGroups->statusOptions();
		} elseif ( $typeKey === 'tags') {
			$options = $this->plugin->tagGroups->statusOptions();
		}
		return $options;
	}

	/**
	 * This method converts filter input criteria into query criteria.
	 * @param array $input
	 * @return array
	 */
	public function formatCriteria($input): array
	{
		$criteria = [];
		foreach($input as &$filter) {
			$fieldHandle = $filter['fieldHandle'] ?? null;
			$filterType = $filter['filterType'] ?? null;
			$value = $filter['value'] ?? null;
			$newCriteria = $this->plugin->fieldTypes->fieldCriteria($fieldHandle, $filterType, $value);
			// In case of multiple "relatedTo" parameters, merge them.
			if ( isset($newCriteria['relatedTo']) ) {
				// If we haven't already added the "relatedTo" parameter, we need the "and".
				if ( ! isset($criteria['relatedTo']) ) {
					$criteria['relatedTo'] = ['and'];
				}
				$criteria['relatedTo'] = array_merge($criteria['relatedTo'], [$newCriteria['relatedTo']]);
			// Other types of criteria may just be merged as usual.
			} else {
				$criteria = array_merge($criteria, $newCriteria);
			}
		}
		// If not filtering by status, make sure Craft doesn't do it either.
		if ( ! isset($criteria['status']) ) {
			$criteria['status'] = null;
		}
		// If not filtering by siteId, make sure Craft doesn't do it either.
		if ( ! isset($criteria['siteId']) ) {
			$criteria['siteId'] = null;
		}
		return $criteria;
	}

	/**
	 * This method converts an array of elements to a CSV file stored in the Craft
	 * temp path.
	 */
	public function generateCsvFile($entries, $basename)
	{
		// Items in the array might be objects, convert the object(s) to an array.
		$arrayContent = ArrayHelper::toArray($entries);
		foreach($arrayContent as $rowIndex => &$record) {
			// Let's add the column names as a row to the CSV array content.
			if ( $rowIndex === 0 ) {
				array_unshift($arrayContent, array_keys($record));
			}
			// There may be array values in each item array. We need to flatten those.
			foreach($record as $fieldName => &$fieldValue) {
				if ( is_array($fieldValue) ) {
					$fieldValue = json_encode($fieldValue);
				}
			}
		}
		$csvContent = $this->arrayToCsv($arrayContent);
		$filePath = Craft::$app->path->getTempPath().DIRECTORY_SEPARATOR.$basename.'.csv';
		FileHelper::writeToFile($filePath, $csvContent);
		return file_exists($filePath) ? $filePath : null;
	}

	/**
	 * This method converts an array of arrays content to a CSV string.
	 * @param array
	 * @return string
	 */
	public function arrayToCsv($arr=[]): string
	{
		ob_start();
		$f = fopen('php://output', 'w') or show_error("Can't open php://output");
		foreach ($arr as &$line) {
			fputcsv($f, $line, ',');
		}
		fclose($f) or show_error("Can't close php://output");
		$csvContent = ob_get_contents();
		ob_end_clean();
		return (string) $csvContent;
	}

}
