import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class FormActionComponent extends SilverStripeComponent {
    constructor(props) {
        super(props);

        this.handleClick = this.handleClick.bind(this);
    }

    render() {
        return (
            <button type={this.props.type} className={this.getButtonClasses()} onClick={this.handleClick}>
                {this.getLoadingIcon()}
                {this.props.label}
            </button>
        );
    }

    /**
     * Returns the necessary button classes based on the given props
     *
     * @returns string
     */
    getButtonClasses() {
        var buttonClasses = 'btn';

        // Add 'type' class
        buttonClasses += ` btn-${this.props.style}`;

        // If there is no text
        if (typeof this.props.label === 'undefined') {
        	buttonClasses += ' no-text';
        }

        // Add icon class
        if (typeof this.props.icon !== 'undefined') {
            buttonClasses += ` font-icon-${this.props.icon}`;
        }

        // Add loading class
        if (this.props.loading === true) {
            buttonClasses += ' btn--loading';
        }

        // Add disabled class
        if (this.props.disabled === true) {
            buttonClasses += ' disabled';
        }

        return buttonClasses;
    }

    /**
     * Returns markup for the loading icon
     *
     * @returns object|null
     */
    getLoadingIcon() {
        if (this.props.loading) {
            return (
                <div className="btn__loading-icon" >
                    <svg viewBox="0 0 44 12">
                        <circle cx="6" cy="6" r="6" />
                        <circle cx="22" cy="6" r="6" />
                        <circle cx="38" cy="6" r="6" />
                    </svg>
                </div>
            );
        }

        return null;
    }

    /**
     * Event handler triggered when a user clicks the button.
     *
     * @param object event
     * @returns null
     */
    handleClick(event) {
        this.props.handleClick(event);
    }

}

FormActionComponent.propTypes = {
    handleClick: React.PropTypes.func.isRequired,
    label: React.PropTypes.string,
    type: React.PropTypes.string,
    loading: React.PropTypes.bool,
    icon: React.PropTypes.string,
    disabled: React.PropTypes.bool,
    style: React.PropTypes.string
};

FormActionComponent.defaultProps = {
    type: 'button',
    style: 'secondary'
};

export default FormActionComponent;
