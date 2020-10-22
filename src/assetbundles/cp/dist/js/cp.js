
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
	$(newFilter).find("input, select, textarea").each(function() {
		var newName = $(this).attr("name").replace(/(\d+)/g, filterCount);
		$(this).attr("name", newName);
		//$(this).attr("data-idx", filterCount);
		$(this).val("");
		$(this).prop('readonly', false);
	});
	// Increment all the data-idx attributes found in this new filter block.
	$(newFilter).find("[data-idx]").each(function() {
		$(this).attr("data-idx", filterCount);
	});
	// Clear the filter type options from the middle field.
	$(newFilter).find("[data-select-filter-type]").html('<option value="" > -- </option>');
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
	} else {
		valueField.attr('readonly', false);
		valueField.prop('disabled', false);
	}
}
