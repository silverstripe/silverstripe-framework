import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';

class GridFieldCellComponent extends SilverStripeComponent {

    render() {
        return (
            <div className={this.getCellClassNames()}>{this.props.children}</div>
        );
    }

    getCellClassNames() {
        var cellClassNames = 'grid-field-cell-component ';

        if (typeof this.props.width !== 'number') {
            cellClassNames += 'cell-width-5';
        } else if (this.props.width > 10) {
            cellClassNames += 'cell-width-10';
        } else if (this.props.width < 1) {
            cellClassNames += 'cell-width-1';
        } else {
            cellClassNames += `cell-width-${this.props.width}`;
        }

        return cellClassNames;
    }

}

GridFieldCellComponent.PropTypes = {
    width: React.PropTypes.number
}

export default GridFieldCellComponent;
