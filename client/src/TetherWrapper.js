/*
 * Tether needs to be on the `window` object so BootStrap
 * JS modules can access it.
 */

import Tether from 'tether';
window.Tether = Tether;
export default Tether;
