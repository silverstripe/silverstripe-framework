import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';

class GridFieldHeaderCellComponent extends SilverStripeComponent {

    render() {
        return (
            <div className='grid-field-header-cell-component'>{this.props.children}</div>
        );
    }

}

export default GridFieldHeaderCellComponent;
