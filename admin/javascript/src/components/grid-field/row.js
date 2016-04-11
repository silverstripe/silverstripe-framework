import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldRowComponent extends SilverStripeComponent {

  render() {
    const className = `grid-field-row-component [ list-group-item ] ${this.props.className}`;
    return <li className={className}>{this.props.children}</li>;
  }
}

export default GridFieldRowComponent;
