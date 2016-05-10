/**
 * This wraps the global jQuery so jQuery can be imported
 * like other modules. Once jQuery is updated and managed
 * by npm we can get rid of this wrapper.
 */
var jQuery = typeof window.jQuery !== 'undefined' ? window.jQuery : null;

module.exports = jQuery;
