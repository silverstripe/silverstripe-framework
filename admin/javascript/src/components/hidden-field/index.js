import React from 'react';
import SilverStripeComponent from 'silverstripe-component.js';

class HiddenFieldComponent extends SilverStripeComponent {

    constructor(props) {
        super(props);

        this.handleChange = this.handleChange.bind(this);
    }

    render() {
        return (
            <div className='field hidden'>
                <input {...this.getInputProps()} />
            </div>
        );
    }

    getInputProps() {
        return {
            className: ['hidden', this.props.extraClass].join(' '),
            id: this.props.name,
            name: this.props.name,
            onChange: this.props.onChange,
            type: 'hidden',
            value: this.props.value
        };
    }

    handleChange(event) {
        if (typeof this.props.onChange === 'undefined') {
            return;
        }

        this.props.onChange();
    }
}

HiddenFieldComponent.propTypes = {
    label: React.PropTypes.string,
    extraClass: React.PropTypes.string,
    name: React.PropTypes.string.isRequired,
    onChange: React.PropTypes.func,
    value: React.PropTypes.string
};

export default HiddenFieldComponent;
