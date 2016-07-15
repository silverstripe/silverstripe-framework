import React from 'react';
import { Popover, OverlayTrigger } from 'react-bootstrap-4';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class PopoverField extends SilverStripeComponent {

  render() {
    const placement = this.getPlacement();
    const overlay = (
      <Popover id={`${this.props.id}_Popover`} className={`fade in popover-${placement}`}
        title={this.getPopoverTitle()}
      >
        {this.props.children}
      </Popover>
    );
    // If no text is specified, use ... with xl icon style
    const buttonClasses = ['btn', 'btn-secondary', 'btn--no-focus'];
    if (!this.props.title) {
      buttonClasses.push('font-icon-dot-3 btn--icon-xl');
    }
    return (
      <OverlayTrigger rootClose trigger="click" container={this}
        placement={placement} overlay={overlay}
      >
        <button id={this.props.id} type="button" className={buttonClasses.join(' ')}>
          {this.props.title}
        </button>
      </OverlayTrigger>
    );
  }

  /**
   * Get popup placement direction
   *
   * @returns {String}
   */
  getPlacement() {
    const placement = this.getDataProperty('placement');
    return placement || 'bottom';
  }

  /**
   * Gets title of popup box
   *
   * @return {String} Return the string to use.
   */
  getPopoverTitle() {
    const title = this.getDataProperty('popoverTitle');
    return title || '';
  }

  /**
   * Search for a given property either passed in to data or as a direct prop
   *
   * @param {String} name
   * @returns {String}
   */
  getDataProperty(name) {
    if (typeof this.props[name] !== 'undefined') {
      return this.props[name];
    }

    // In case this is nested in the form schema data prop
    if (
      typeof this.props.data !== 'undefined'
      && typeof this.props.data[name] !== 'undefined'
    ) {
      return this.props.data[name];
    }

    return null;
  }
}

PopoverField.propTypes = {
  id: React.PropTypes.string,
  title: React.PropTypes.string,
  popoverTitle: React.PropTypes.string,
  placement: React.PropTypes.oneOf(['top', 'right', 'bottom', 'left']),
  data: React.PropTypes.shape({
    popoverTitle: React.PropTypes.string,
    placement: React.PropTypes.oneOf(['top', 'right', 'bottom', 'left']),
  }),
};

export default PopoverField;
