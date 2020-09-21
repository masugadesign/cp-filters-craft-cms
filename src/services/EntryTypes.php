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
				$types[(string) $entryType->name] = $entryType;
			}
		} else {
			foreach($filterableTypeIds as &$id) {
				$entryType = $sectionsService->getEntryTypeById($id);
				$types[(string) $entryType->name] = $entryType;
			}
		}
		// Sort the resulting array by type name (keys).
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
		foreach($types as &$type) {
			// We'll add the section name if the type name is different.
			$typeName = (string) $type->name;
			$sectionName = (string) $type->getSection()->name;
			$label = ($typeName !== $sectionName) ? "{$sectionName} - {$typeName}" : $typeName;
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
			$fields = $layout->getFields();
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

}
