import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldCellComponent extends SilverStripeComponent {

    render() {
        return (
            <td className='grid-field-cell-component'>{this.props.children}</td>
        );
    }

}

export default GridFieldCellComponent;
