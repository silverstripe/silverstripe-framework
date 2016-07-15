import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

/**
 * Empty container for the moment, will eventually contain the CMS menu`
 * and apply to document.body, rather than just one specific DOM element.
 */
class App extends SilverStripeComponent {
  render() {
    return (<div className="app">{this.props.children}</div>);
  }
}

export default App;
