import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class NorthHeader extends SilverStripeComponent {

  render() {
    return (
      <div className="toolbar--north container-fluid">
        {this.props.children}
      </div>
    );
  }
}

export default NorthHeader;
