<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
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
		$savedFilter->filterElementType = $input['filterElementType'] ?? $savedFilter->filterElementType;
		$savedFilter->filterGroupId = $input['filterGroupId'] ?? $savedFilter->filterGroupId;
		$savedFilter->filterCriteria = $input['filterCriteria'] ?? $savedFilter->filterCriteria;
		$savedFilter->includeDrafts = $input['includeDrafts'] ?? $savedFilter->filterCriteria;
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
		$criteria = $element->filterCriteria;
		$groupId = $element->filterGroupId;
		$filterUrl;

		$filterUrl = UrlHelper::cpUrl('cpfilters/' . $elementTypeKey, [
			'elementType' => $elementTypeKey,
			'groupId' => $groupId,
			'filters' => json_decode($criteria),
			'includeDrafts' => $element->includeDrafts
		]);
		return $filterUrl;
	}
}
