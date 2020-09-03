<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\base\FieldInterface;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use Masuga\CpFilters\base\Service;
use yii\helpers\Inflector;

/**
 * The CP Filters Field Types service.
 * @author Masuga Design
 */
class FieldTypes extends Service
{

	/**
	 * A place for fields we don't want to fetch again in the same request.
	 * @var array
	 */
	private $cachedFields = [];

	/**
	 * The array of filter types and their respective search syntax.
	 * @var array
	 */
	const FILTER_TYPES = [
		'contains' => '*[value]*',
		'starts with' => '[value]*',
		'ends with' => '*[value]',
		'is equal to' => '[value]',
		'is assigned' => '[value]',
		'is greater than' => '> [value]',
		'is less than' => '< [value]',
		'is empty' => ':empty:',
		'is not empty' => ':notempty:'
	];

	/**
	 * The array of supported field types for filtering.
	 * @var array
	 */
	const FIELD_TYPES = [
		'craft\fields\Assets' => ['is empty', 'is not empty'],
		'craft\fields\Categories' => ['is assigned', 'is empty', 'is not empty'],
		'craft\fields\Checkboxes' => ['contains', 'is empty', 'is not empty'],
		'craft\fields\Date' => ['is greater than', 'is less than', 'is empty', 'is not empty'],
		'craft\fields\Dropdown' => ['is equal to', 'is empty', 'is not empty'],
		'craft\fields\Entries' => ['is assigned', 'is empty', 'is not empty'],
		'craft\fields\Matrix' => ['is empty', 'is not empty'],
		'craft\fields\Number' => ['is equal to', 'is greater than', 'is less than', 'is empty', 'is not empty'],
		'craft\fields\PlainText' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
		'craft\fields\RadioButtons' => ['is equal to', 'is empty', 'is not empty'],
		'craft\redactor\Field' => ['contains', 'is empty', 'is not empty'],
	];

	/**
	 * These are the native Craft fields that should also be filterable. It's the
	 * field handle and its respective type.
	 * @var array
	 */
	const NATIVE_FIELD_MAPS = [
		'id' => 'craft\fields\Number',
		'title' => 'craft\fields\PlainText',
		'slug' => 'craft\fields\PlainText',
		'postDate' => 'craft\fields\Date',
		'dateCreated' => 'craft\fields\Date',
		'dateUpdated' => 'craft\fields\Date',
	];

	/**
	 * This method returns the full array of supported field types and their filter options.
	 * @return array
	 */
	public function fieldTypes()
	{
		$additionalFieldTypes = $this->plugin->getSettings()->additionalFieldTypes ?? [];
		return array_merge(self::FIELD_TYPES, $additionalFieldTypes);
	}

	/**
	 * This method returns an array of the field types that support filtering in
	 * one form or another.
	 * @return array
	 */
	public function getSupportedFieldTypes(): array
	{
		return array_keys($this->fieldTypes());
	}

	/**
	 * This method fetches a Craft field by its handle and caches it to this particular
	 * instance of the FieldTypes service class. If you look under the hood in Craft,
	 * it appears that all fields are already queried at some point and it is actually
	 * using the ArrayHelper to fetch the matching field anyway but we'll do our own
	 * "flashing" here just in case.
	 * @param string $handle
	 * @return FieldInterface|null
	 */
	public function getFieldByHandle($handle)
	{
		$field = null;
		if ( !empty($this->cachedFields[$handle]) ) {
			$field = $this->cachedFields[$handle];
		} else {
			$fieldsService = Craft::$app->getFields();
			$field = $fieldsService->getFieldByHandle($handle);
			if ( $field ) {
				$this->cachedFields[$handle] = $field;
			}
		}
		return $field;
	}

	/**
	 * This method returns an array of filter type options for a given field
	 * based on its field handle. Once the field is fetched, it is actually the
	 * "type" that determines what the filter options are.
	 * @param string $handle
	 * @param bool $asHtml
	 * @param string $selectedValue
	 * @return array|string
	 */
	public function getFilterOptionsByFieldHandle($handle, $asHtml=true, $selectedValue=null)
	{
		// Initialize the possible return value.
		$options = [];
		$optionsHtml = '';
		$fieldTypes = $this->fieldTypes();
		// First check if we are looking for a native field.
		if ( isset(self::NATIVE_FIELD_MAPS[$handle]) ) {
			$type = self::NATIVE_FIELD_MAPS[$handle];
			$options = $fieldTypes[ $type ] ?? [];
		// Using the Craft Fields service, attempt to find the field by handle.
		} else {
			$field = $this->getFieldByHandle($handle);
			// We need to use its type to determine which filter options are available.
			if ( $field ) {
				$type = get_class($field);
				$options = $fieldTypes[ $type ] ?? [];
			}
		}
		if ( !empty($options) && $asHtml === true ) {
			foreach($options as &$option) {
				$selected = ($selectedValue && $selectedValue === $option) ? 'selected="selected"' : '';
				$optionsHtml .= "<option value='{$option}' {$selected} >{$option}</option>";
			}
		}
		$returnValue = ($asHtml === true) ? $optionsHtml : $options;
		return $returnValue;
	}

	/**
	 * This method attempts to fetch a field's options by its handle. This is for
	 * dropdowns, checkboxes, radios... I think that's it.
	 * @param string $handle
	 * @return array
	 */
	public function getFieldOptionsByFieldHandle($handle): array
	{
		$options = [];
		$field = $this->getFieldByHandle($handle);
		if ( $field ) {
			if ( $field instanceof BaseRelationField ) {
				$options = $this->getRelationFieldOptionsByField($field);
			} else {
				$fieldOptions = $field->options ?? [];
				foreach($fieldOptions as &$option) {
					$options[$option['value']] = $option['label'];
				}
			}
		}
		return $options;
	}

	/**
	 * This method creates an associative array of element IDs to element titles
	 * for any sources that would be selectable in a given relation field.
	 * @param BaseRelationField $field
	 * @return array
	 */
	public function getRelationFieldOptionsByField($field): array
	{
		$options = $actualSources = [];
		// These values are "keys" which consist of a group type, a colon, then the UID. Worthless.
		$sourceOptions = $field->source ?: $field->sources;
		if ( ! is_array($sourceOptions) ) {
			$sourceOptions = [$sourceOptions];
		}
		// Kludge to determine which type of elements we're dealing with.
		$elementType = stripos(get_class($field), 'Categ') !== false ? 'craft\elements\Category' : 'craft\elements\Entry';
		foreach($sourceOptions as &$sourceOption) {
			//$uid = stripos($sourceOption, '*') === false ? substr($sourceOption, strpos($sourceOption, ':')+1) : null;
			if ( $sourceOption !== '*' ) {
				$source = ElementHelper::findSource($elementType, $sourceOption);
				$actualSources[] = (string) $source['data']['handle'];
			}
		}
		// Query the elements in the appropriate manner based on their type.
		if ( stripos(get_class($field), 'Categ') !== false ) {
			$elements = Category::find()->group($actualSources)->anyStatus()->orderBy('title')->limit(null)->all();
		} else {
			$elements = Entry::find()->section($actualSources)->anyStatus()->orderBy('title')->limit(null)->all();
		}
		foreach($elements as &$element) {
			$options[ $element->id ] = $element->title;
		}
		return ['' => 'Select Value...'] + $options;
	}

	/**
	 * This method generates a single piece of element criteria for a given
	 * field handle, filter type and optional value.
	 * @param string $fieldHandle,
	 * @param string $filterType
	 * @param mixed $value
	 * @return array
	 */
	public function fieldCriteria($fieldHandle, $filterType, $value=null): array
	{
		// Be careful of "garbage in".
		if ( $fieldHandle && $filterType ) {
			if ( $filterType === 'is assigned' ) {
				$criteria = ['relatedTo' => ['field' => $fieldHandle, 'targetElement' => $value]];
			} else {
				$criteria = [$fieldHandle => str_replace('[value]', $value, self::FILTER_TYPES[$filterType])];
			}
		} else {
			$criteria = [];
		}
		return $criteria;
	}

	/**
	 * This method returns an associative array of native field handles to labels.
	 * @return array
	 */
	public function nativeFields(): array
	{
		$fields = [];
		foreach(self::NATIVE_FIELD_MAPS as $handle => &$type) {
			$fields[$handle] = Inflector::camel2words($handle);
		}
		return $fields;
	}

	/**
	 * This method creates a string preview of an entry field's value.
	 * @param Entry $entry
	 * @param string $fieldHandle
	 * @return string
	 */
	public function previewValue(&$entry, $fieldHandle): string
	{
		$preview = '';
		$type = is_object($entry->{$fieldHandle}) ? get_class($entry->{$fieldHandle}) : gettype($entry->{$fieldHandle});
		$value = $entry->{$fieldHandle};
		if ( $type === 'DateTime' ) {
			$preview = $value->format('Y-m-d H:i:s');
		} elseif ( $type === 'craft\elements\db\AssetQuery' ) {
			$oneFile = $value->anyStatus()->one();
			if ( $oneFile ) {
				$thumbUrl = $oneFile->kind === 'image' ? $oneFile->getUrl([
					'width' => 100,
					'height' => 100,
					'mode' => 'fit',
					'quality' => 80
				]) : Craft::$app->getAssets()->getThumbUrl($oneFile, 100);
				$preview = (string) $oneFile->kind === 'image' ?
					'<a href="'.$oneFile->url.'" target="_blank" ><img src="'.$thumbUrl.'" alt="'.$oneFile->filename.'" title="'.$oneFile->filename.'" ></a>' :
					$oneFile->filename;
			} else {
				$preview = '--';
			}
		} elseif ( $type === 'craft\elements\db\EntryQuery' ) {
			$oneEntry = $value->anyStatus()->one();
			$preview = $oneEntry ? $oneEntry->title : '--';
		} elseif ( $type === 'craft\elements\db\CategoryQuery' ) {
			$oneCat = $value->anyStatus()->one();
			$preview = $oneCat ? '<a href="'.$oneCat->getCpEditUrl().'" target="_blank" >'.$oneCat->title.'</a>' : '--';
		} elseif ( $type === 'craft\fields\data\MultiOptionsFieldData' ) {
			$preview = implode(', ', (array) $value);
		} elseif ( $type === 'craft\elements\db\MatrixBlockQuery' ) {
			$preview = $value->count(). ' items';
		} else {
			$preview = $value;
		}
		return (string) $preview;
	}

}
