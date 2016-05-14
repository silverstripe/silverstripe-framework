import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class FormAction extends SilverStripeComponent {
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
      buttonClasses.push('btn--no-text');
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
    if (this.props.loading === true) {
      return (
        <div className="btn__loading-icon" >
          <span className="btn__circle btn__circle--1" ></span>
          <span className="btn__circle btn__circle--2" ></span>
          <span className="btn__circle btn__circle--3" ></span>
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

FormAction.propTypes = {
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

FormAction.defaultProps = {
  type: 'button',
  bootstrapButtonStyle: 'secondary',
  disabled: false,
};

export default FormAction;
