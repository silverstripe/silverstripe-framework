import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldRowComponent extends SilverStripeComponent {

    render() {
        return (
            <li className='grid-field-row-component [ list-group-item ]'>{this.props.children}</li>
        );
    }

}

export default GridFieldRowComponent;
