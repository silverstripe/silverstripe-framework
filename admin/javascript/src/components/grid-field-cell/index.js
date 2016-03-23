import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';

class GridFieldCellComponent extends SilverStripeComponent {

    render() {
        return (
            <div className='grid-field-cell-component'>{this.props.children}</div>
        );
    }

}

export default GridFieldCellComponent;
