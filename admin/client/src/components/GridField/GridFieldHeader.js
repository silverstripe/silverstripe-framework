import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import GridFieldRow from './GridFieldRow';

class GridFieldHeader extends SilverStripeComponent {

  render() {
    return (
      <GridFieldRow>{this.props.children}</GridFieldRow>
    );
  }

}

export default GridFieldHeader;
