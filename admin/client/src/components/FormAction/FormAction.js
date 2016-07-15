import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class FormAction extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    return (
      <button {...this.getButtonProps()}>
        {this.getLoadingIcon()}
        {this.props.title}
      </button>
    );
  }

  /**
   * Get props for the button
   *
   * @returns {Object}
   */
  getButtonProps() {
    // Merge attributes
    return Object.assign({},
      typeof this.props.attributes === 'undefined' ? {} : this.props.attributes,
      {
        id: this.props.id,
        className: this.getButtonClasses(),
        disabled: this.props.disabled,
        onClick: this.handleClick,
      }
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
    const bootstrapStyle = this.getBootstrapButtonStyle();
    if (bootstrapStyle) {
      buttonClasses.push(`btn-${bootstrapStyle}`);
    }

    // If there is no text
    if (typeof this.props.title === 'undefined') {
      buttonClasses.push('btn--no-text');
    }

    // Add icon class
    const icon = this.getIcon();
    if (icon) {
      buttonClasses.push(`font-icon-${icon}`);
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
   * Gets the bootstrap classname for this action
   *
   * @return {String}
   */
  getBootstrapButtonStyle() {
    // Add 'type' class
    if (typeof this.props.bootstrapButtonStyle !== 'undefined') {
      return this.props.bootstrapButtonStyle;
    }

    if (this.props.name === 'action_save') {
      return 'primary';
    }

    return 'secondary';
  }

  /**
   * Get icon name
   *
   * @returns {String}
   */
  getIcon() {
    // In case this is specified directly
    return this.props.icon || this.props.data.icon || null;
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
          <span className="btn__circle btn__circle--1" />
          <span className="btn__circle btn__circle--2" />
          <span className="btn__circle btn__circle--3" />
        </div>
      );
    }

    return null;
  }

  /**
   * Event handler triggered when a user clicks the button.
   *
   * @param {Object} event
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
  title: React.PropTypes.string,
  type: React.PropTypes.string,
  loading: React.PropTypes.bool,
  icon: React.PropTypes.string,
  disabled: React.PropTypes.bool,
  bootstrapButtonStyle: React.PropTypes.string,
  extraClass: React.PropTypes.string,
  attributes: React.PropTypes.object,
};

FormAction.defaultProps = {
  title: '',
  icon: '',
  attributes: {},
  data: {},
  disabled: false,
};

export default FormAction;
