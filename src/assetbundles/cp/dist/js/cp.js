
refreshSlimSelects();

// An index of added filters. We'll never subtract from it (to avoid index collisions).
var filterCount = $(".filterField").length;

// On page load, toggle appropriate field readonly/disable
$(".filterField").each(function() {
	var index = $(this).attr("data-idx");
	toggleValueFieldReadonly(index);
});

// JS
$("body").on("change", "#groupId", function() {
	// If entry type changes, remove all current filters.
	$(".filterFields").html('');
	$("#filtersForm").submit();
});

// Add another filter by cloning this filter, emptying values and updating its numeric index.
$(".filterFields").on("mouseup touchup", "[data-add-filter]", function() {
	// Increment the global filter counter.
	filterCount ++;
	var currentFilter = $(this).parent();
	var newFilter = currentFilter.clone();
	// Increment the index values in the field attributes.
	$(newFilter).find("input[type='text'], input[type='number'], select, textarea").each(function() {
		var newName = $(this).attr("name").replace(/(\d+)/g, filterCount);
		$(this).attr("name", newName);
		//$(this).attr("data-idx", filterCount);
		$(this).val("");
		$(this).prop('readonly', false);
	});
	// The copied filter value select should have options removed to avoid confusion.
	$(newFilter).find("select[data-filter-value]").each(function() {
		$(this).html("");
		$(this).css('display', 'block');
	});
	// Increment all the data-idx attributes found in this new filter block.
	$(newFilter).find("[data-idx]").each(function() {
		$(this).attr("data-idx", filterCount);
	});
	// Clear the filter type options from the middle field.
	$(newFilter).find("[data-select-filter-type]").html('<option value="" > -- </option>');
	// Remove any existing instances of SlimSelect.
	$(newFilter).find(".ss-main").remove();
	$(newFilter).insertAfter(currentFilter);
});

// Remove a filter from the list.
$(".filterFields").on("mouseup touchup", "[data-remove-filter]", function() {
	if ( $(".filterField").length > 1 ) {
		$(this).parent().remove();
	} else {
		alert("Don't remove the last filter.");
	}
});

// Update the filter type options based on the selected field handle.
$(".filterFields").on("change", "[data-select-field]", function() {
	var index = $(this).attr("data-idx");
	var handle = $(this).val();
	var elementTypeKey = $("#elementTypeKey").val();
	if ( handle ) {
		// Populate the filter types dropdown with options.
		$.ajax({
			'url' : $("#fieldFilterOptionsUrl").val(),
			'type' : 'GET',
			'dataType' : 'html',
			'context' : $("select[data-select-filter-type][data-idx='"+index+"']"),
			'data' : {
				'fieldHandle' : handle,
				'elementTypeKey' : elementTypeKey
			},
			'success' : function(data, textStatus, jqXHR) {
				$(this).html(data);
				// Load the appropriate "value" field.
				$.ajax({
					'url' : $("#valueFieldUrl").val(),
					'type' : 'GET',
					'dataType' : 'html',
					'context' : $(".valueFieldContainer[data-idx='"+index+"']"),
					'data' : {
						'fieldHandle' : handle,
						'index' : index,
						'elementTypeKey' : elementTypeKey
					},
					'success' : function(data, textStats, jqXHR) {
						$(this).html(data);
						toggleValueFieldReadonly(index);
						refreshSlimSelects();
					}
				});
			}
		});
	}
});

// When the filter type changes, determine if the value field should be readonly.
$(".filterFields").on("change", "[data-select-filter-type]", function() {
	var index = $(this).attr("data-idx");
	toggleValueFieldReadonly(index);
});

// Toggle the "readonly" property on the value field based on filter type.
function toggleValueFieldReadonly(index)
{
	var filterType = $("[data-select-filter-type][data-idx='"+index+"']").val();
	var valueField = $("[data-filter-value][data-idx='"+index+"']");
	// Some filter types do not take values into account at all. Let's make that clear.
	if ( filterType == 'is empty' || filterType == 'is not empty' ) {
		valueField.val('');
		valueField.attr('readonly', true);
		valueField.prop('disabled', true);
		if ( typeof valueField[0].slim !== "undefined" ) {
			valueField[0].slim.disable();
		}
	} else {
		valueField.attr('readonly', false);
		valueField.prop('disabled', false);
		if ( typeof valueField[0].slim !== "undefined" ) {
			valueField[0].slim.enable();
		}
	}
}

// Initialize SlimSelect on filter value selects that don't already have ssid.
function refreshSlimSelects()
{
	if ( $("select[data-filter-value]:not([data-ssid])").length ) {
		$("select[data-filter-value]:not([data-ssid])").each(function() {
			new SlimSelect({
				select: '#'+$(this).attr('id'),
				placeholder: 'Select...',
				showSearch: true,
				searchText: 'No matches found.'
			});
		});
	}
}

// Open "Save Filter" modal on click to "Save Filter" button
var $saveFilterModal = $("#saveFilterModal");
var modal = new Garnish.Modal($saveFilterModal, { autoShow: false });
$("#saveFilter").on("click", function(){
	$($saveFilterModal).addClass('modal');
	modal.show();
});

// Close "Save Filter" modal on click to "Cancel" modal button
$("#closeFilterModal").on("click", function(){
	modal.hide();
});

// Save filter on click to "Save Filter" modal button
$("#saveFilterButton").on("click", function(e){
	e.preventDefault();

	// Submit the filter form to make sure all selected filters are saved
	$("#filtersForm").submit();

	// Get the userId and filter title onto the main form
	var userIdInput      = $("input[name='userId']");
	var filterTitleInput = $("input[name='filterTitle']");

	// Use the primary criteria-creation form,
	// but submit it with a different action
	// to run the input through the Filters::formatCriteria($input) method
	// to create the savedFilter url
	var actionUrl = $("input[name='saveFilterAction']").val();
	var formData = $("#filtersForm").serializeArray();
	formData.push(
		{name: 'userId', value: $(userIdInput).val()},
		{name: 'filterTitle', value: $(filterTitleInput).val()},
		{'name' : window.csrfTokenName, 'value' : window.csrfTokenValue}
	);

	$.ajax({
		"type": "POST",
		"url": actionUrl,
		"dataType": "json",
		"data": formData,
		"success": function(data, textStatus, jqXHR) {
			modal.hide();
		},
		"error": function(jqXHR, textStatus, errorThrown) {
			modal.hide();
		}
	});
});


// Delete filter on click to "Delete Filter" button
$(".deleteFilterButton").on("click", function(e){
	e.preventDefault();

	var thisForm  = $(this).parents("form.deleteFilterForm")
	var actionUrl = $(thisForm).attr('data-action');
	var formData  = $(thisForm).serializeArray();
	var thisRow   = $(thisForm).closest(".filter-row");

	$.ajax({
		"type": "POST",
		"url": actionUrl,
		"dataType": "json",
		"data": formData,
		"success": function(data, textStatus, jqXHR) {
			// Remove this row from the table
			$(thisRow).remove();
		},
		"error": function(jqXHR, textStatus, errorThrown) {
			console.log(errorThrown);
		}
	});
});
