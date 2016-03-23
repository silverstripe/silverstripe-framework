import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';

class GridFieldTableComponent extends SilverStripeComponent {

    render() {
        return (
            <ul className='grid-field-table-component [ list-group ]'>
                {this.generateHeader()}
                {this.generateRows()}
            </ul>
        );
    }

    /**
     * Generates the header component.
     *
     * Uses the header component passed via the `header` prop if it exists.
     * Otherwise generates a header from the `data` prop.
     *
     * @return object|null
     */
    generateHeader() {
        if (typeof this.props.header !== 'undefined') {
            return this.props.header;
        }

        if (typeof this.props.data !== 'undefined') {
            // TODO: Generate the header.
        }

        return null;
    }

    /**
     * Generates the table rows.
     *
     * Uses the components passed via the `rows` props if it exists.
     * Otherwise generates rows from the `data` prop.
     *
     * @return object|null
     */
    generateRows() {
        if (typeof this.props.rows !== 'undefined') {
            return this.props.rows;
        }

        if (typeof this.props.data !== 'undefined') {
            // TODO: Generate the rows.
        }

        return null;
    }

}

GridFieldTableComponent.propTypes = {
    data: React.PropTypes.object,
    header: React.PropTypes.object,
    rows: React.PropTypes.array
};

export default GridFieldTableComponent;
