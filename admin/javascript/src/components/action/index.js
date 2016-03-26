import React from 'react';
import ReactDOM from 'react-dom';
import SilverStripeComponent from 'silverstripe-component.js';

class ActionComponent extends SilverStripeComponent {
    constructor(props) {
        super(props);

        this.handleClick = this.handleClick.bind(this);
    }

    render() {
        return (
            <button className={this.getButtonClasses()} onClick={this.handleClick}>
                {this.getLoadingIcon()}
                {this.props.text}
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

        // If there is no text
        if (typeof this.props.text === 'undefined') {
        	buttonClasses += ' no-text';
        }

        // Add 'type' class
        if (this.props.type === 'danger') {
            buttonClasses += ' btn-danger';
        } else if (this.props.type === 'success') {
            buttonClasses += ' btn-success';
        } else if (this.props.type === 'primary') {
            buttonClasses += ' btn-primary';
        } else if (this.props.type === 'link') {
            buttonClasses += ' btn-link';
        } else if (this.props.type === 'secondary') {
            buttonClasses += ' btn-secondary';
        } else if (this.props.type === 'complete') {
            buttonClasses += ' btn-success-outline';
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

ActionComponent.propTypes = {
    handleClick: React.PropTypes.func.isRequired,
    type: React.PropTypes.string,
    icon: React.PropTypes.string,
    text: React.PropTypes.string,
    loading: React.PropTypes.bool,
    disabled: React.PropTypes.bool
};

export default ActionComponent;
