<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\ArrayHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\base\Service;

/**
 * The Products filters service.
 * @author Masuga Design
 */
class ProductGroups extends Service
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
		$typeService = CommercePlugin::getInstance()->getProductTypes();
		$filterableTypeIds = $this->plugin->getSettings()->filterableProductTypeIds;
		// If it isn't an array of IDs, get all the entry types.
		if ( ! is_array($filterableTypeIds) ) {
			$filterableTypeIds = [];
			$allProductTypes = $typeService->getAllProductTypes();
			foreach($allProductTypes as &$productType) {
				$types[(string) $productType->name] = $productType;
			}
		} else {
			foreach($filterableTypeIds as &$id) {
				$productType = $typeService->getEntryTypeById($id);
				$types[(string) $productType->name] = $productType;
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
		$options = [];
		foreach($types as &$type) {
			$typeName = (string) $type->name;
			$label = $typeName;
			$options[(int) $type->id] = $label;
		}
		asort($options);
		return ['' => 'Select Product Type...'] + $options;
	}

	/**
	 * This method fetches an associative array of field objects.
	 * @param int $typeId
	 * @return array
	 */
	public function fields($typeId): array
	{
		$fields = [];
		$typeService = CommercePlugin::getInstance()->getProductTypes();
		$productType = $typeService->getProductTypeById($typeId);
		if ( $productType ) {
			// Get an array of supported field type from the plugin's FieldTypes service.
			$fieldTypes = $this->plugin->fieldTypes->getSupportedFieldTypes();
			$layout = $productType->fieldLayout;
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

	/**
	 * This method returns an array of appropriate status options for entries.
	 * @return array
	 */
	public function statusOptions(): array
	{
		$options = array_merge(['' => 'Select Status...'], Product::statuses());
		return $options;
	}

}
