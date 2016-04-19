import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldHeaderCellComponent extends SilverStripeComponent {

  render() {
    return (
      <th>{this.props.children}</th>
    );
  }

}

GridFieldHeaderCellComponent.PropTypes = {
  width: React.PropTypes.number,
};

export default GridFieldHeaderCellComponent;
