/**
 * Reusable DataTable Utilities for Sprintalyze
 * Based on raidplanner2 datatable implementation
 */

/**
 * Helper function to construct datatable endpoint URLs
 * @param {string} path - The endpoint path (e.g., '/available-users.json')
 * @returns {string} The full datatable URL
 */
function datatableUrl(path) {
    return '/datatable' + path;
}

/**
 * Helper function to construct AJAX endpoint URLs
 * @param {string} path - The endpoint path
 * @returns {string} The full AJAX URL
 */
function ajaxUrl(path) {
    return '/ajax' + path;
}

/**
 * Creates and initializes a DataTable with consistent styling and layout
 * @param {string} dompath - The CSS selector for the table element (e.g., '#my-table')
 * @param {object} obj - DataTable configuration object
 * @returns {object} The initialized DataTable instance
 */
function makeTable(dompath, obj) {
    // Initialize the DataTable
    let datatable = $(dompath).DataTable(obj);

    // Adjust datatable elements as created in this template
    let wrapper = $(dompath).parents('.dataTables_wrapper');

    if (wrapper.length) {
        // Move filter to right and style it
        wrapper.find('.dataTables_filter').addClass('pull-right')
            .find('label').addClass('panel-ctrls-center');
        wrapper.find('.dataTables_filter label.panel-ctrls-center input')
            .attr('placeholder', 'Search...');

        // Move length selector to left and style it
        wrapper.find('.dataTables_length').addClass('pull-left')
            .find('label').addClass('panel-ctrls-center');

        // Style pagination
        wrapper.find('.dataTables_paginate>ul.pagination')
            .addClass('pull-right m-n');

        // Move controls to panel header if it exists
        let controlsDiv = wrapper.parents('.panel').find('.panel-ctrls');
        if (controlsDiv.length) {
            controlsDiv.append(wrapper.find('.dataTables_filter'));
            controlsDiv.append('<i class="separator"></i>');
            controlsDiv.append(wrapper.find('.dataTables_length'));
        }

        // Move pagination to panel footer if it exists
        let footerDiv = wrapper.parents('.panel').find('.panel-footer');
        if (footerDiv.length) {
            footerDiv.append(wrapper.find('.dataTable+.row'));
        }
    }

    // Add reload function for easier table refresh
    datatable.fnDraw = function(pReInit) {
        this.ajax.reload(null, pReInit);
    };

    return datatable;
}

/**
 * Enhanced AJAX wrapper with error handling
 * @param {object} obj - jQuery AJAX configuration object
 */
function ajaxJson(obj) {
    if (obj.dataType === undefined) {
        obj.dataType = 'json';
    }

    let successFn = obj.success;
    obj.success = function(result) {
        // Handle API errors
        if (result.error !== undefined && result.error) {
            alert(result.error);
        }
        // Handle session loss
        else if (result.session !== undefined && result.session === 'lost') {
            window.location.href = '/login';
        }
        else {
            if (typeof(successFn) === 'function') {
                successFn(result);
            }
        }
    };

    $.ajax(obj);
}

/**
 * Helper function to submit form on Enter key press
 * @param {Event} event - The keypress event
 * @param {Element} elem - The form element
 */
function submitFormOnEnter(event, elem) {
    if (event.which === 13 || event.code === "Enter") {
        let submitButton = $(elem).parents('.modal-dialog').find('.form-submit');
        if (submitButton.length) {
            submitButton.click();
        }
    }
}
