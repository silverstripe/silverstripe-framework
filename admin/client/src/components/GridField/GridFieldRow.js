import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class GridFieldRow extends SilverStripeComponent {

  render() {
    const className = `grid-field__row ${this.props.className}`;
    return <tr tabIndex="0" className={className}>{this.props.children}</tr>;
  }
}

export default GridFieldRow;
