import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldRowComponent extends SilverStripeComponent {

    render() {
        return (
            <tr className='grid-field-row-component'>{this.props.children}</tr>
        );
    }

}

export default GridFieldRowComponent;
