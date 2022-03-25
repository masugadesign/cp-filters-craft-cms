<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\elements\Category;
use craft\helpers\ArrayHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\base\Service;

/**
 * The CategoryGroups filters service.
 * @author Masuga Design
 */
class CategoryGroups extends Service
{

	/**
	 * This method fetches an array of the filterable category groups based on the IDs
	 * supplied in the plugin config.
	 * @return array
	 */
	public function fetchFilterableGroups(): array
	{
		// Initialize the return value.
		$groups = [];
		$categoriesService = Craft::$app->getCategories();
		$filterableGroupIds = $this->plugin->getSettings()->filterableCategoryGroupIds;
		// If it isn't an array of IDs, get all the category groups.
		if ( ! is_array($filterableGroupIds) ) {
			$filterableGroupIds = [];
			$allGroups = $categoriesService->getAllGroups();
			foreach($allGroups as &$group) {
				$groups[(string) $group->name] = $group;
			}
		} else {
			foreach($filterableGroupIds as &$id) {
				$group = $categoriesService->getGroupById($id);
				$groups[(string) $group->name] = $group;
			}
		}
		// Sort the resulting array by type name (keys).
		ksort($groups);
		return $groups;
	}

	/**
	 * This method returns an array of value -> label pairs to be used as select
	 * element option elements.
	 * @return array
	 */
	public function groupOptions(): array
	{
		$groups = $this->fetchFilterableGroups();
		$options = [];
		foreach($groups as &$group) {
			// We'll add the section name if the type name is different.
			$groupName = (string) $group->name;
			$options[(int) $group->id] = $groupName;
		}
		asort($options);
		return ['' => 'Select Category Group...'] + $options;
	}

	/**
	 * This method fetches an associative array of field objects.
	 * @param int $groupId
	 * @return array
	 */
	public function fields($groupId): array
	{
		$fields = [];
		$categoriesService = Craft::$app->getCategories();
		$group = $categoriesService->getGroupById($groupId);
		if ( $group ) {
			// Get an array of supported field type from the plugin's FieldTypes service.
			$fieldTypes = $this->plugin->fieldTypes->getSupportedFieldTypes();
			$layout = $group->fieldLayout;
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
	 * This method returns an array of appropriate status options for categories.
	 * @return array
	 */
	public function statusOptions(): array
	{
		$options = array_merge(['' => 'Select Status...'], Category::statuses());
		return $options;
	}

}
