# CP Filters for Craft CMS

### Advanced filtering for elements in the control panel.
This is a commercial plugin for Craft CMS 3 that is in BETA. The plugin has not yet been released for public use and is not supported.

### Table of Contents

### Requirements

* Craft CMS v3.0.0+
* PHP 7.0+

### Installation

Add the following to your composer.json requirements. Be sure to adjust the version number to match the version you wish to install.

```
"masugadesign/cpfilters": "1.0.0",
```

### Settings

### Config

#### filterableEntryTypeIds

This is an array of entry __type__ IDs, not to be confused with the __section__ IDs.

```
<?php

'filterableEntryTypeIds' => [1,5,10]
```
