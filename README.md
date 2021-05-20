# CP Filters for Craft CMS

![CP Filters for Craft CMS](https://www.gomasuga.com/uploads/software/cpfilters-entries-multiple-filters.jpg)

### Advanced filtering for Entry elements in the control panel.

### Table of Contents

### Requirements

* Craft CMS v3.3.16+
* PHP 7.0+

### Installation

Add the following to your composer.json requirements. Be sure to adjust the version number to match the version you wish to install.

```
"masugadesign/cpfilters": "1.1.1.1",
```

### Config

The following settings may be configured in a **cpfilters.php** config file.

#### filterableEntryTypeIds

This is an array of entry __type__ IDs, not to be confused with the __section__ IDs.

```
'filterableEntryTypeIds' => [1,5,10],
```

#### filterableAssetVolumeIds

Specify an array of Asset volume IDs to restrict which volumes are filterable.

```
'filterableAssetVolumeIds' => [2,3,8],
```

#### filterableCategoryGroupIds

Specify an array of Category group IDs to restrict which groups are filterable.

```
'filterableCategoryGroupIds' => [1,2,4,5,6,10],
```

#### filterableTagGroupIds

Specify an array of Tag group IDs to restrict which groups are filterable.

```
'filterableTagGroupIds' => [1,2],
```

#### additionalFieldTypes

Register custom field types as filterable by supplying the fully qualified class name and an array of filter options. The following filter options are available. Be careful to choose appropriate filter options because not all field types can
support all the filters.

Filter options: `contains`, `starts with`, `ends with`, `is equal to`, `is assigned`, `is greater than`, `is less than`, `is empty`, `is not empty`

```
<?php
'additionalFieldTypes' = [
	'modules\masuga\fields\CategoriesMultipleSources' => ['is assigned', 'is empty', 'is not empty']
],
```

### Saved Filters
You can save the set of currently selected filters to easily view the results later. Filters are saved per User.

![Saved Filters](https://www.gomasuga.com/uploads/software/cpfilters-entries-saved-filters.jpg)

### Planned Features

- Filter by more element types: Assets, Users, Orders, Products
