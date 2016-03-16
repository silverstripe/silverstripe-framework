import React from 'react';
import SilverStripeComponent from 'silverstripe-component';
import GridFieldRowComponent from '../grid-field-row';

class GridFieldHeaderComponent extends SilverStripeComponent {

    render() {
        return (
            <thead className='grid-field-header-component'>
                <GridFieldRowComponent>{this.props.children}</GridFieldRowComponent>
            </thead>
        );
    }

}

export default GridFieldHeaderComponent;
