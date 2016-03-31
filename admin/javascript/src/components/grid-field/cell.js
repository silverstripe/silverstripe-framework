import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldCellComponent extends SilverStripeComponent {

    render() {
        return (
            <div className='grid-field-cell-component'>{this.props.children}</div>
        );
    }

}

GridFieldCellComponent.PropTypes = {
    width: React.PropTypes.number
}

export default GridFieldCellComponent;
