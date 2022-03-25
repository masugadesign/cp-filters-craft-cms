<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\elements\Tag;
use craft\helpers\ArrayHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\base\Service;

/**
 * The TagGroups filters service.
 * @author Masuga Design
 */
class TagGroups extends Service
{

	/**
	 * This method fetches an array of the filterable tag groups based on the IDs
	 * supplied in the plugin config.
	 * @return array
	 */
	public function fetchFilterableGroups(): array
	{
		// Initialize the return value.
		$groups = [];
		$tagsService = Craft::$app->getTags();
		$filterableGroupIds = $this->plugin->getSettings()->filterableTagGroupIds;
		// If it isn't an array of IDs, get all the tag groups.
		if ( ! is_array($filterableGroupIds) ) {
			$filterableGroupIds = [];
			$allGroups = $tagsService->getAllTagGroups();
			foreach($allGroups as &$group) {
				$groups[(string) $group->name] = $group;
			}
		} else {
			foreach($filterableGroupIds as &$id) {
				$group = $tagsService->getTagGroupById($id);
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
		return ['' => 'Select Tag Group...'] + $options;
	}

	/**
	 * This method fetches an associative array of field objects.
	 * @param int $groupId
	 * @return array
	 */
	public function fields($groupId): array
	{
		$fields = [];
		$tagsService = Craft::$app->getTags();
		$group = $tagsService->getTagGroupById($groupId);
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
	 * This method returns an array of appropriate status options for tags.
	 * @return array
	 */
	public function statusOptions(): array
	{
		$options = array_merge(['' => 'Select Status...'], Tag::statuses());
		return $options;
	}

}
