import React from 'react';
import { Popover, OverlayTrigger } from 'react-bootstrap-4';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class PopoverField extends SilverStripeComponent {

  render() {
    const placement = this.getPlacement();
    const overlay = (
      <Popover id={`${this.props.id}_Popover`} className={`fade in popover-${placement}`}
        title={this.props.data.popoverTitle}
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
    const placement = this.props.data.placement;
    return placement || 'bottom';
  }
}

PopoverField.propTypes = {
  id: React.PropTypes.string,
  title: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.bool]),
  data: React.PropTypes.oneOfType([
    React.PropTypes.array,
    React.PropTypes.shape({
      popoverTitle: React.PropTypes.string,
      placement: React.PropTypes.oneOf(['top', 'right', 'bottom', 'left']),
    }),
  ]),
};

export default PopoverField;
