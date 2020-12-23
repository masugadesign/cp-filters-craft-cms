<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use Masuga\CpFilters\base\Service;
use Masuga\CpFilters\elements\SavedFilter;
use Masuga\CpFilters\elements\db\SavedFilterQuery;
use Masuga\CpFilters\records\SavedFilterRecord;

class SavedFilters extends Service
{
	/**
	 * This method creates/updates a CPFilters SavedFilter element based on whether or
	 * not an existing filter ID is supplied.
	 * @param array $input
	 * @param int $id
	 * @return SavedFilter|null
	 */
	public function saveFilter($input=[], $id)
	{
		$savedFilter = null;
		if ( $id ) {
			$savedFilter = SavedFilter::find()->id($id)->one();
		}
		if ( ! $savedFilter ) {
			$savedFilter = new SavedFilter();
		}
		$savedFilter->siteId = Craft::$app->getSites()->currentSite->id;
		$savedFilter->title = $input['title'];
		$savedFilter->filterElementType = $input['filterElementType']?? $savedFilter->filterElementType;
		$savedFilter->filterGroupId = $input['filterGroupId']?? $savedFilter->filterGroupId;
		$savedFilter->filterCriteria = $input['filterCriteria'] ?? $savedFilter->filterCriteria;
		$savedFilter->userId = $input['userId'];
		// On a successful save, return the savedFilter element itself.
		return Craft::$app->getElements()->saveElement($savedFilter) ? $savedFilter : null;
	}

	/**
	 * This method returns a saved filter based on provided id.
	 * @param int $id
	 * @return array
	 */
	public function getFilter($id)
	{
		return SavedFilterRecord::find()
		->where(['id' => $id])
		->andWhere(['dateDeleted' => null])
		->one();
	}

	/**
	 * This method returns an array of saved filters based on provided criteria.
	 * @param array $criteria
	 * @return object
	 */
	public function savedFilters($criteria)
	{
		$query = SavedFilter::find();
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}

	/**
	 * This method deletes a filter based on the provided id.
	 * @param int $elementId
	 * @return bool
	 */
	public function deleteSavedFilter($elementId)
	{
		$thisFilter = $this->getFilter($elementId);
		if ($thisFilter) {
			$success = $thisFilter->softDelete();
			return $success;
		} else {
			return null;
		}
	}

	public function parseFieldCriteria(array $criteria): string
	{
		$fieldsString = '';
		$i = 0;
		foreach ($criteria as $key => $val) {
			// Relationships fields have subarrays
			if ( $key === 'relatedTo' ) {
				$joiner = $val[0];

				// Get each relationship in that field,
				// skipping 0 because that's the joiner
				for ($j=1; $j < count($val) ; $j++) {
					$field = $val[$j]['field'];
					$targetElement = $val[$j]['targetElement'];
					$fieldsString = $fieldsString . "&filters[${i}][fieldHandle]=${field}&filters[${i}][filterType]=is+assigned&filters[${i}][value]=${targetElement}";
					// increment loop here, too, because each iteration is still another field
					$i++;
				}
			} elseif($val != null) {
				// @TODO: figure out value encoding for native, custom fields of non-relationship types
				$fieldsString = $fieldsString . "&filters[${i}][fieldHandle]=${key}&filters[${i}][filterType]=${val}";
				$i++;
			}
		}

		return $fieldsString;
	}

	/**
	 * This method takes a saved filter's information and returns a url to
	 * use to show the results of that filter.
	 * @param int $elementTypeKey
	 * @param array $criteria
	 * @param int $groupId
	 * @return array
	 */
	public function createFilterUrl($element): ?string
	{
		$elementTypeKey = $element->filterElementType;
		$criteria = json_decode($element->filterCriteria, true);
		$groupId = $element->filterGroupId;
		$filterUrl;

		// No filter without elementType
		if ($elementTypeKey) {
			$filterUrl = "?elementType=${elementTypeKey}";

			// groupId is optional; add if present
			if ($groupId) {
				$filterUrl = $filterUrl . "&groupId=${groupId}";
			}

			// Get the decoded field criteria
			if ($criteria) {
				$fieldCriteria = $this->parseFieldCriteria($criteria);

				$filterUrl = $filterUrl . $fieldCriteria;
			}

		} else {
		}

		return $filterUrl;
	}
}