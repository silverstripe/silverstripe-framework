import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldCellComponent extends SilverStripeComponent {

  constructor(props) {
    super(props);
    this.handleDrillDown = this.handleDrillDown.bind(this);
  }

  render() {
    const props = {
      className: `grid-field-cell-component ${this.props.className}`,
      onClick: this.handleDrillDown,
    };

    return (
      <div {...props}>{this.props.children}</div>
    );
  }


  handleDrillDown(event) {
    if (typeof this.props.handleDrillDown === 'undefined') {
      return;
    }

    this.props.handleDrillDown(event);
  }

}

GridFieldCellComponent.PropTypes = {
  width: React.PropTypes.number,
  handleDrillDown: React.PropTypes.func,
};

export default GridFieldCellComponent;
