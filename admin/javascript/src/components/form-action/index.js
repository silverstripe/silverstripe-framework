import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class FormActionComponent extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    const props = {
      type: this.props.type,
      className: this.getButtonClasses(),
      disabled: this.props.disabled,
      onClick: this.handleClick,
    };

    if (typeof this.props.id !== 'undefined') {
      props.id = this.props.id;
    }

    return (
      <button {...props}>
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
    const buttonClasses = ['btn'];

    // Add 'type' class
    buttonClasses.push(`btn-${this.props.bootstrapButtonStyle}`);

    // If there is no text
    if (typeof this.props.label === 'undefined') {
      buttonClasses.push('no-text');
    }

    // Add icon class
    if (typeof this.props.icon !== 'undefined') {
      buttonClasses.push(`font-icon-${this.props.icon}`);
    }

    // Add loading class
    if (this.props.loading === true) {
      buttonClasses.push('btn--loading');
    }

    // Add disabled class
    if (this.props.disabled === true) {
      buttonClasses.push('disabled');
    }

    if (typeof this.props.extraClass !== 'undefined') {
      buttonClasses.push(this.props.extraClass);
    }

    return buttonClasses.join(' ');
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
   * @return undefined
   */
  handleClick(event) {
    if (typeof this.props.handleClick === 'undefined') {
      return;
    }

    this.props.handleClick(event);
  }

}

FormActionComponent.propTypes = {
  id: React.PropTypes.string,
  handleClick: React.PropTypes.func,
  label: React.PropTypes.string,
  type: React.PropTypes.string,
  loading: React.PropTypes.bool,
  icon: React.PropTypes.string,
  disabled: React.PropTypes.bool,
  bootstrapButtonStyle: React.PropTypes.string,
  extraClass: React.PropTypes.string,
};

FormActionComponent.defaultProps = {
  type: 'button',
  bootstrapButtonStyle: 'secondary',
  disabled: false,
};

export default FormActionComponent;
