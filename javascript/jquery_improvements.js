// play nice with prototype
jQuery.noConflict();

/**
 * Clears the selected form elements.  Takes the following actions on the matched elements:
 *  - input text fields will have their 'value' property set to the empty string
 *  - select elements will have their 'selectedIndex' property set to -l. Normann change it from -1 to '', 
 *    since set to -1, actually is not clearing the field, it change its value to null, and when submit the form the field belonged to,
 *    the field value will be treated to a pure string "null", and this is not what it suppose to do.
 *  - checkbox and radio inputs will have their 'checked' property set to false
 *  - inputs of type submit, button, reset, and hidden will *not* be effected
 *  - button elements will *not* be effected
 *
 * @example $('.myInputs').clearFields();
 * @desc Clears all inputs with class myInputs
 *
 * @name clearFields
 * @type jQuery
 * @cat Plugins/Form
 */
(function($) {
$.fn.clearFields = $.fn.clearInputs = function() {
    return this.each(function() {
        var t = this.type, tag = this.tagName.toLowerCase();
        if (t == 'text' || t == 'password' || tag == 'textarea')
            this.value = '';
        else if (t == 'checkbox' || t == 'radio')
            this.checked = false;
        else if (tag == 'select')
			//changed by Normann@silvestripe.com, see document above
            this.selectedIndex = '';
    });
};
})(jQuery);