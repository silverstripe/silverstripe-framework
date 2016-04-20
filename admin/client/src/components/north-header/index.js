import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class NorthHeaderComponent extends SilverStripeComponent {

  render() {
    return (
      <div className="toolbar--north container-fluid">
        {this.props.children}
      </div>
    );
  }
}

export default NorthHeaderComponent;
