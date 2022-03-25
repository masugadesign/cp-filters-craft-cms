<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\elements\Order;
use craft\helpers\ArrayHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\base\Service;

/**
 * The Orders filters service.
 * @author Masuga Design
 */
class OrderGroups extends Service
{

	/**
	 * There are no types or groups for Orders
	 * @return array
	 */
	public function fetchFilterableGroups(): array
	{
		// Initialize the return value.
		$types = [];
		return $types;
	}

	/**
	 * This method returns an array of value -> label pairs to be used as select
	 * element option elements.
	 * @return array
	 */
	public function groupOptions(): array
	{
		return ['' => ''];
	}

	/**
	 * This method fetches an associative array of field objects.
	 * @param int $typeId
	 * @return array
	 */
	public function fields(): array
	{
		$fields = [];
		// Get an array of supported field type from the plugin's FieldTypes service.
		$fieldTypes = $this->plugin->fieldTypes->getSupportedFieldTypes();
		$order = new Order;
		$layout = $order->getFieldLayout();
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
	 * This method returns an array of appropriate status options for entries.
	 * @return array
	 */
	public function statusOptions(): array
	{
		$values = [];
		$allStatuses = CommercePlugin::getInstance()->getOrderStatuses()->getAllOrderStatuses();

		// Switching the status handle and ID since PHP doesn't like numeric array keys
		foreach ($allStatuses as $status) {
			$key = (string) $status['id'];
			$value = $status['name'];
			$values[$value] = $key;
		}

		$options = array_merge(['Select Status...' => ''], $values);
		return $options;
	}

}
