import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldHeaderCellComponent extends SilverStripeComponent {

  render() {
    return (
      <div className="grid-field-header-cell-component">{this.props.children}</div>
    );
  }

}

GridFieldHeaderCellComponent.PropTypes = {
  width: React.PropTypes.number,
};

export default GridFieldHeaderCellComponent;
