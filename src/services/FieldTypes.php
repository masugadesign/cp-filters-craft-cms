<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\base\FieldInterface;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\fields\Assets;
use craft\fields\BaseRelationField;
use craft\fields\Lightswitch;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\records\Volume;
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
		'craft\fields\Assets' => ['is assigned', 'is empty', 'is not empty'],
		'craft\fields\Categories' => ['is assigned', 'is empty', 'is not empty'],
		'craft\fields\Checkboxes' => ['contains', 'is empty', 'is not empty'],
		'craft\fields\Date' => ['is greater than', 'is less than', 'is empty', 'is not empty'],
		'craft\fields\Dropdown' => ['is equal to', 'is empty', 'is not empty'],
		'craft\fields\Entries' => ['is assigned', 'is empty', 'is not empty'],
		'craft\fields\Lightswitch' => ['is equal to'],
		'craft\fields\Matrix' => ['is empty', 'is not empty'],
		'craft\fields\Number' => ['is equal to', 'is greater than', 'is less than', 'is empty', 'is not empty'],
		'craft\fields\PlainText' => ['contains', 'starts with', 'ends with', 'is equal to', 'is empty', 'is not empty'],
		'craft\fields\RadioButtons' => ['is equal to', 'is empty', 'is not empty'],
		'craft\fields\Tags' => ['is assigned', 'is empty', 'is not empty'],
		'craft\fields\Users' => ['is assigned', 'is empty', 'is not empty'],
		'craft\redactor\Field' => ['contains', 'is empty', 'is not empty'],
		'STATUS' => ['is equal to'],
		'ORDERSTATUS' => ['is equal to']
	];

	/**
	 * These are the native Craft fields that should also be filterable. It's the
	 * field handle and its respective type.
	 * @var array
	 */
	const NATIVE_FIELD_MAPS = [
		'id' => 'craft\fields\Number',
		'username' => 'craft\fields\PlainText',
		'firstName' => 'craft\fields\PlainText',
		'lastName' => 'craft\fields\PlainText',
		'email' => 'craft\fields\PlainText',
		//'groupId' => 'craft\fields\Number',
		'filename' => 'craft\fields\PlainText',
		'title' => 'craft\fields\PlainText',
		'slug' => 'craft\fields\PlainText',
		'siteId' => 'craft\fields\Number',
		'status' => 'STATUS',
		'postDate' => 'craft\fields\Date',
		'dateCreated' => 'craft\fields\Date',
		'dateUpdated' => 'craft\fields\Date',
		'orderStatus' => 'ORDERSTATUS'
	];

	/**
	 * This method returns the list of excluded native fields by element type key.
	 * @param string $typeKey
	 * @return array
	 */
	public function excludedFields($typeKey): array
	{
		$excludes = [];
		if ( $typeKey === 'entries' ) {
			$excludes = ['username','firstName','lastName','email','filename','groupId','orderStatus'];
		} elseif ( $typeKey === 'assets' ) {
			$excludes = ['username','firstName','lastName','email','groupId','postDate','status','orderStatus'];
		} elseif ( $typeKey === 'users' ) {
			$excludes = ['title','filename','postDate','orderStatus'];
		} elseif ( $typeKey === 'categories' ) {
			$excludes = ['username','firstName','lastName','email','filename','groupId','postDate','orderStatus'];
		} elseif ( $typeKey === 'tags') {
			$excludes = ['username','firstName','lastName','email','filename','groupId','postDate','orderStatus'];
		} elseif ( $typeKey == 'orders' ) {
			$excludes = ['username','firstName','lastName','email','filename','groupId','status'];
		} elseif ( $typeKey == 'products' ) {
			$excludes = ['username','firstName','lastName','email','filename','groupId','orderStatus'];
		}
		return $excludes;
	}

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
			} elseif ( $field instanceof Lightswitch ) {
				$options = [
					'1' => $field->onLabel,
					'0' => $field->offLabel
				];
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
	public function getRelationFieldOptionsByField(&$field): array
	{
		$options = $actualSources = [];
		// These values are "keys" which consist of a group type, a colon, then the UID. Worthless.
		if ( $field instanceof Assets ) {
			$sourceOptions = $this->getAssetsFieldSourceOptions($field);
		} else {
			// These fields' settings are so confusing. Single source items still have '*' in sources... sometimes.
			$sourceOptions = $field->sources && $field->sources !== '*' ? $field->sources : $field->source;
		}
		if ( ! is_array($sourceOptions) ) {
			$sourceOptions = [$sourceOptions];
		}
		// BaseRelationField::elementType() is protected so that's a no-go.
		$elementType = $this->detectRelationType($field);
		foreach($sourceOptions as &$sourceOption) {
			//$uid = stripos($sourceOption, '*') === false ? substr($sourceOption, strpos($sourceOption, ':')+1) : null;
			if ( $sourceOption !== '*' ) {
				// ElementHelper::findSource() doesn't appear to work with asset volumes. It returns NULL?
				$source = ( ! $field instanceof Assets ) ?
					ElementHelper::findSource($elementType, $sourceOption) :
					$this->getVolumeIdBySourceKey($sourceOption);
				$actualSources[] = $source['data']['handle'] ?? $source['criteria']['groupId'] ?? $source;
			} else {
				$actualSources = '*';
			}
		}
		// No generalized way to query sources on ElementQuery so we need to be specific with our elements here.
		if ( $elementType === 'craft\elements\Asset' ) {
			$elements = Asset::find()->volumeId($actualSources)->anyStatus()->orderBy('title')->limit(300)->all();
		} elseif ( $elementType === 'craft\elements\Category' ) {
			$elements = Category::find()->group($actualSources)->anyStatus()->orderBy('title')->limit(300)->all();
		} elseif ( $elementType === 'craft\elements\Entry' ) {
			$elements = Entry::find()->section($actualSources)->anyStatus()->orderBy('title')->limit(300)->all();
		} elseif ( $elementType === 'craft\elements\Tag' ) {
			$elements = Tag::find()->groupId($actualSources)->anyStatus()->orderBy('title')->limit(300)->all();
		} elseif ( $elementType === 'craft\elements\User' ) {
			$elements = User::find()->groupId($actualSources)->anyStatus()->orderBy('username')->limit(300)->all();
		}
		foreach($elements as &$element) {
			$options[ $element->id ] = $element->title ?? $element->username ?? "ID: {$element->id}";
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
			if ( $fieldHandle == 'orderStatus' ) {
				$criteria = [ 'orderStatusId' => str_replace('[value]', $value, self::FILTER_TYPES[$filterType]) ];
			} elseif ( $filterType === 'is assigned' ) {
				$criteria = ['relatedTo' => ['field' => $fieldHandle, 'targetElement' => $value]];
			} elseif ( $filterType === 'assigned count less than' ) {

			} elseif ( $filterType === 'assigned count greater than' ) {

			} else {
				$criteria = [$fieldHandle => str_replace('[value]', $value, self::FILTER_TYPES[$filterType])];
			}
		} else {
			$criteria = [];
		}
		return $criteria;
	}

	/**
	 * This method returns an associative array of native field handles to labels
	 * with an optional array parameter containing a list of fields to exclude.
	 * @param array $exclude
	 * @return array
	 */
	public function nativeFields($exclude=[]): array
	{
		$fields = [];
		foreach(self::NATIVE_FIELD_MAPS as $handle => &$type) {
			if ( ! in_array($handle, $exclude) ) {
				$fields[$handle] = Inflector::camel2words($handle);
			}
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
			$entries = $value->anyStatus()->orderBy('title')->limit(5)->all();
			$ellipsis = count($entries) === 5 ? "&hellip;" : "";
			$preview = $entries ? implode(', ', ArrayHelper::getColumn($entries, 'title')).$ellipsis : '--';
		} elseif ( $type === 'craft\elements\db\CategoryQuery' ) {
			$oneCat = $value->anyStatus()->one();
			$preview = $oneCat ? '<a href="'.$oneCat->getCpEditUrl().'" target="_blank" >'.$oneCat->title.'</a>' : '--';
		} elseif ( $type === 'craft\fields\data\MultiOptionsFieldData' ) {
			$preview = implode(', ', (array) $value);
		} elseif ( $type === 'craft\elements\db\MatrixBlockQuery' ) {
			$preview = $value->count(). ' items';
		} elseif ( $type === 'craft\elements\db\TagQuery' ) {
			$tags = $value->anyStatus()->orderBy('title')->all();
			$preview = $tags ? implode(', ', ArrayHelper::getColumn($tags, 'title')) : '--';
		} elseif ( $type === 'craft\elements\db\UserQuery' ) {
			$users = $value->anyStatus()->orderBy('username')->limit(5)->all();
			$ellipsis = count($users) === 5 ? "&hellip;" : "";
			$preview = $users ? implode(', ', ArrayHelper::getColumn($users, 'username')).$ellipsis : '--';
		} else {
			$preview = $value;
		}
		return (string) $preview;
	}

	/**
	 * Craft relation fields do not publicly expose which type of elements they
	 * relate to. I have no idea why this is. So here is a kludgy, but probably
	 * reliable, method for determining the element type.
	 * @param BaseRelationField $field
	 * @return string
	 */
	public function detectRelationType(BaseRelationField &$field): string
	{
		$type = '';
		$class = get_class($field);
		if ( stripos($class, 'Asset') !== false ) {
			$type = Asset::class;
		} elseif ( stripos($class, 'Categ') !== false ) {
			$type = Category::class;
		} elseif ( stripos($class, 'Entr') !== false ) {
			$type = Entry::class;
		} elseif ( stripos($class, 'Tag') !== false ) {
			$type = Tag::class;
		} elseif ( stripos($class, 'User') !== false ) {
			$type = User::class;
		}
		return $type;
	}

	/**
	 * This returns the volume IDs that are allowable for a particular Assets field.
	 * Is it the source? The sources? The singleUploadLocationSource? We'll find out
	 * here and return an array of volume key(s).
	 * @param Assets $field
	 * @return array
	 */
	public function getAssetsFieldSourceOptions(Assets &$field): array
	{
		$volumes = [];
		if ( $field->singleUploadLocationSource ) {
			$volumes[] = $field->singleUploadLocationSource;
		} elseif ( $field->source ) {
			$volumes[] = $field->source;
		} else {
			$volumes = $field->sources;
		}
		return $volumes;
	}

	/**
	 * This method returns an Asset volume ID based on its volume source key.
	 * @param string $sourceKey
	 * @return Volume|null
	 */
	public function getVolumeIdBySourceKey($sourceKey)
	{
		$volumeUid = explode(':', $sourceKey)[1] ?? null;
		$volume = $volumeUid ? Craft::$app->getVolumes()->getVolumeByUid($volumeUid) : null;
		$volumeId = $volume->id ?? null;
		return $volumeId;
	}

}
