import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

/**
 * Empty container for the moment, will eventually contain the CMS menu`
 * and apply to document.body, rather than just one specific DOM element.
 */
class App extends SilverStripeComponent {
  render() {
    // TODO re-add <div className="app"> wrapper when applying to document.body
    const Child = React.Children.only(this.props.children);
    return (Child);
  }
}

export default App;
