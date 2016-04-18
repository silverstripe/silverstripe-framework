import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class NorthHeaderComponent extends SilverStripeComponent {

  render() {
    return (
      <div className="north-header container-fluid">
        <div className="north-header__navigation">
          {this.props.children}
        </div>
      </div>
    );
  }
}

export default NorthHeaderComponent;
