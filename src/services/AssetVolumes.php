<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\base\Service;

/**
 * The AssetVolumes filter service.
 * @author Masuga Design
 */
class AssetVolumes extends Service
{

	/**
	 * This method fetches an array of the filterable asset volumes based on the IDs
	 * supplied in the plugin config.
	 * @return array
	 */
	public function fetchFilterableGroups(): array
	{
		// Initialize the return value.
		$volumes = [];
		$volumesService = Craft::$app->getVolumes();
		$filterableVolumeIds = $this->plugin->getSettings()->filterableAssetVolumeIds;
		// If it isn't an array of IDs, get all the asset volumes.
		if ( ! is_array($filterableVolumeIds) ) {
			$filterableVolumeIds = [];
			$allVolumes = $volumesService->getAllVolumes();
			foreach($allVolumes as &$volume) {
				$volumes[(string) $volume->name] = $volume;
			}
		} else {
			foreach($filterableVolumeIds as &$id) {
				$volume = $volumesService->getVolumeById($id);
				$volumes[(string) $volume->name] = $volume;
			}
		}
		// Sort the resulting array by volume name (keys).
		ksort($volumes);
		return $volumes;
	}

	/**
	 * This method returns an array of value -> label pairs to be used as select
	 * element option elements.
	 * @return array
	 */
	public function groupOptions(): array
	{
		$volumes = $this->fetchFilterableGroups();
		foreach($volumes as &$volume) {
			// We'll add the section name if the volume name is different.
			$volumeName = (string) $volume->name;
			$options[(int) $volume->id] = $volumeName;
		}
		asort($options);
		return ['' => 'Select Asset Volume...'] + $options;
	}

	/**
	 * This method fetches an associative array of field objects.
	 * @param int $volumeId
	 * @return array
	 */
	public function fields($volumeId): array
	{
		$fields = [];
		$volumesService = Craft::$app->getVolumes();
		$volume = $volumesService->getVolumeById($volumeId);
		if ( $volume ) {
			// Get an array of supported field type from the plugin's FieldTypes service.
			$fieldTypes = $this->plugin->fieldTypes->getSupportedFieldTypes();
			$layout = $volume->fieldLayout;
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
	 * This method returns an array of appropriate status options for assets.
	 * @return array
	 */
	public function statusOptions(): array
	{
		$options = array_merge(['' => 'Select Status...'], Asset::statuses());
		return $options;
	}

}
