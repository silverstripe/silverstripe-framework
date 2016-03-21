import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';

class TextFieldComponent extends SilverStripeComponent {

    constructor(props) {
        super(props);

        this.handleChange = this.handleChange.bind(this);
    }

    render() {
        return (
            <div className='field text'>
                {this.props.label &&
                    <label className='left' htmlFor={'gallery_' + this.props.name}>
                        {this.props.label}
                    </label>
                }
                <div className='middleColumn'>
                    <input {...this.getInputProps()} />
                </div>
            </div>
        );
    }

    getInputProps() {
        return {
            className: ['text', this.props.extraClass].join(' '),
            id: `gallery_${this.props.name}`,
            name: this.props.name,
            onChange: this.props.onChange,
            type: 'text',
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

TextFieldComponent.propTypes = {
    label: React.PropTypes.string,
    extraClass: React.PropTypes.string,
    name: React.PropTypes.string.isRequired,
    onChange: React.PropTypes.func,
    value: React.PropTypes.string
};

export default TextFieldComponent;
