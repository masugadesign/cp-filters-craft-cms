<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\base\Service;

/**
 * This Entry Filters Entry Types service.
 * @author Masuga Design
 */
class EntryTypes extends Service
{

	/**
	 * This method fetches an array of the filterable entry types based on the IDs
	 * supplied in the plugin config.
	 * @return array
	 */
	public function fetchFilterableGroups(): array
	{
		// Initialize the return value.
		$types = [];
		$sectionsService = Craft::$app->getSections();
		$filterableTypeIds = $this->plugin->getSettings()->filterableEntryTypeIds;
		// If it isn't an array of IDs, get all the entry types.
		if ( ! is_array($filterableTypeIds) ) {
			$filterableTypeIds = [];
			$allEntryTypes = $sectionsService->getAllEntryTypes();
			foreach($allEntryTypes as &$entryType) {
				$key = "{$entryType->section->name} - {$entryType->name}";
				$types[$key] = $entryType;
			}
		} else {
			foreach($filterableTypeIds as &$id) {
				$entryType = $sectionsService->getEntryTypeById($id);
				$key = "{$entryType->section->name} - {$entryType->name}";
				$types[$key] = $entryType;
			}
		}
		// Sort the resulting array by section/type name (keys).
		ksort($types);
		return $types;
	}

	/**
	 * This method returns an array of value -> label pairs to be used as select
	 * element option elements.
	 * @return array
	 */
	public function groupOptions(): array
	{
		$types = $this->fetchFilterableGroups();
		$options = [];
		foreach($types as $sectionTypeKey => &$type) {
			// We'll add the section name if the type name is different.
			$typeName = (string) $type->name;
			$sectionName = (string) $type->getSection()->name;
			// As of Craft 3.7, the default entry type name/handle are Default/default. *sigh*
			$label = ( $typeName === $sectionName ) ? $sectionName : $sectionTypeKey;
			$options[(int) $type->id] = $label;
		}
		asort($options);
		return ['' => 'Select Entry Type...'] + $options;
	}

	/**
	 * This method fetches an associative array of field objects.
	 * @param int $typeId
	 * @return array
	 */
	public function fields($typeId): array
	{
		$fields = [];
		$sectionsService = Craft::$app->getSections();
		$entryType = $sectionsService->getEntryTypeById($typeId);
		if ( $entryType ) {
			// Get an array of supported field type from the plugin's FieldTypes service.
			$fieldTypes = $this->plugin->fieldTypes->getSupportedFieldTypes();
			$layout = $entryType->fieldLayout;
			$fields = $layout->getCustomFields();
			// Loop through each field and make sure it is a supported field type.
			foreach($fields as $index => &$field) {
				if ( ! in_array(get_class($field), $fieldTypes) ) {
					unset($fields[$index]);
				}
			}
			ArrayHelper::multisort($fields, 'name');
		}
		return $fields;
	}

	/**
	 * This method returns an array of appropriate status options for entries.
	 * @return array
	 */
	public function statusOptions(): array
	{
		$options = array_merge(['' => 'Select Status...'], Entry::statuses());
		return $options;
	}

}
