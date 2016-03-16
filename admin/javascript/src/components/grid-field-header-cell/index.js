import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldHeaderCellComponent extends SilverStripeComponent {

    render() {
        return (
            <th className='grid-field-header-cell-component'>{this.props.children}</th>
        );
    }

}

export default GridFieldHeaderCellComponent;
