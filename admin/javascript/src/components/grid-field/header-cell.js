import React from 'react';
import SilverStripeComponent from 'silverstripe-component.js';

class GridFieldHeaderCellComponent extends SilverStripeComponent {

    render() {
        return (
            <div className={this.getHeaderCellClassNames()}>{this.props.children}</div>
        );
    }

    getHeaderCellClassNames() {
        var headerCellClassNames = 'grid-field-header-cell-component ';

        if (typeof this.props.width !== 'number') {
            headerCellClassNames += 'cell-width-5';
        } else if (this.props.width > 10) {
            headerCellClassNames += 'cell-width-10';
        } else if (this.props.width < 1) {
            headerCellClassNames += 'cell-width-1';
        } else {
            headerCellClassNames += `cell-width-${this.props.width}`;
        }

        return headerCellClassNames;
    }

}

GridFieldHeaderCellComponent.PropTypes = {
    width: React.PropTypes.number
}

export default GridFieldHeaderCellComponent;
