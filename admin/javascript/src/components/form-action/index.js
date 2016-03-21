import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';

class FormActionComponent extends SilverStripeComponent {

    render() {
        return (
            <button type={this.props.type} className={this.props.className}>
                {this.props.label}
            </button>
        );
    }

}

FormActionComponent.propTypes = {
    className: React.PropTypes.string,
    label: React.PropTypes.string.isRequired,
    type: React.PropTypes.string
};

FormActionComponent.defaultProps = {
    className: 'btn btn-primary',
    type: 'button'
};

export default FormActionComponent;
