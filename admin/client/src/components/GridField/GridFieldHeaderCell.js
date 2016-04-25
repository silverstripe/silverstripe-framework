import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class GridFieldHeaderCell extends SilverStripeComponent {

  render() {
    return (
      <th>{this.props.children}</th>
    );
  }

}

GridFieldHeaderCell.PropTypes = {
  width: React.PropTypes.number,
};

export default GridFieldHeaderCell;
