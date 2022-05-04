<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\base\Service;

/**
 * The UserGroups filters service. This one is different than the others because
 * user field layouts relate directly to the User element but I figured I'd make
 * a "group" service for consistency.
 * @author Masuga Design
 */
class UserGroups extends Service
{

	/**
	 * This method fetches an array of the filterable user groups based on the IDs
	 * supplied in the plugin config.
	 * @return array
	 */
	public function fetchFilterableGroups(): array
	{
		// Initialize the return value.
		$groups = [];
		$userGroupsService = Craft::$app->getUserGroups();
		$filterableGroupIds = [];
		$allGroups = $userGroupsService->getAllGroups();
		foreach($allGroups as &$group) {
			$groups[(string) $group->name] = $group;
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
		return ['' => 'Select User Group...'] + $options;
	}

	/**
	 * This method fetches an associative array of field objects.
	 * @return array
	 */
	public function fields(): array
	{
		$fields = [];
		$fieldTypes = $this->plugin->fieldTypes->getSupportedFieldTypes();
		$fieldsService = Craft::$app->getFields();
		$layout = $fieldsService->getLayoutByType(User::class);
		$fields = $layout->getCustomFields();
		// Loop through each field and make sure it is a supported field type.
		foreach($fields as $index => &$field) {
			if ( ! in_array(get_class($field), $fieldTypes) ) {
				unset($fields[$index]);
			}
		}
		ArrayHelper::multisort($fields, 'name');
		return $fields;
	}

	/**
	 * This method returns an array of appropriate status options for users.
	 * @return array
	 */
	public function statusOptions(): array
	{
		$options = array_merge(['' => 'Select Status...'], User::statuses());
		return $options;
	}

}
