# CP Filters for Craft CMS

![CP Filters for Craft CMS](https://www.gomasuga.com/uploads/software/cpfilters_craftcms_beta3.jpg?v=beta3)

### Advanced filtering for Entry elements in the control panel.

This is a commercial plugin for Craft CMS 3 that is in BETA. The plugin has not yet been released for public use and is not supported.

### Table of Contents

### Requirements

* Craft CMS v3.3.0+
* PHP 7.0+

### Installation

Add the following to your composer.json requirements. Be sure to adjust the version number to match the version you wish to install.

```
"masugadesign/cpfilters": "1.0.0-beta-7",
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

### Planned Features

- Filter by more element types: Assets, Users, Orders, Products
