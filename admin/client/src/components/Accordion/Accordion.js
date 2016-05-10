import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class Accordion extends SilverStripeComponent {
  render() {
    return (
      <div className="accordion"
        role="tablist"
        aria-multiselectable="true"
      >{this.props.children}</div>
    );
  }
}
export default Accordion;
