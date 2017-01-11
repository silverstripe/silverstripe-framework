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
        <span>{this.props.title}</span>
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
        name: this.props.name,
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
    const style = this.getButtonStyle();

    if (style) {
      buttonClasses.push(`btn-${style}`);
    }

    // If there is no text
    if (typeof this.props.title !== 'string') {
      buttonClasses.push('btn--no-text');
    }

    // Add icon class
    const icon = this.getIcon();
    if (icon) {
      buttonClasses.push(`font-icon-${icon}`);
    }

    // Add loading class
    if (this.props.loading) {
      buttonClasses.push('btn--loading');
    }

    // Add disabled class
    if (this.props.disabled) {
      buttonClasses.push('disabled');
    }

    if (typeof this.props.extraClass === 'string') {
      buttonClasses.push(this.props.extraClass);
    }

    return buttonClasses.join(' ');
  }

  /**
   * Gets the bootstrap classname for this action
   *
   * @return {String}
   */
  getButtonStyle() {
    // Add 'type' class
    if (typeof this.props.data.buttonStyle !== 'undefined') {
      return this.props.data.buttonStyle;
    }

    if (typeof this.props.buttonStyle !== 'undefined') {
      return this.props.buttonStyle;
    }

    const extraClasses = this.props.extraClass.split(' ');

    // defined their own `btn-${something}` class
    if (extraClasses.find((className) => className.indexOf('btn-') > -1)) {
      return null;
    }

    if (this.props.name === 'action_save' ||
        extraClasses.find((className) => className === 'ss-ui-action-constructive')
    ) {
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
    if (this.props.loading) {
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
    if (typeof this.props.handleClick === 'function') {
      this.props.handleClick(event, this.props.name || this.props.id);
    }
  }
}

FormAction.propTypes = {
  id: React.PropTypes.string,
  name: React.PropTypes.string,
  handleClick: React.PropTypes.func,
  title: React.PropTypes.string,
  type: React.PropTypes.string,
  loading: React.PropTypes.bool,
  icon: React.PropTypes.string,
  disabled: React.PropTypes.bool,
  data: React.PropTypes.oneOfType([
    React.PropTypes.array,
    React.PropTypes.shape({
      buttonStyle: React.PropTypes.string,
    }),
  ]),
  extraClass: React.PropTypes.string,
  attributes: React.PropTypes.object,
};

FormAction.defaultProps = {
  title: '',
  icon: '',
  extraClass: '',
  attributes: {},
  data: {},
  disabled: false,
};

export default FormAction;
