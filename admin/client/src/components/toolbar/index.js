import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class ToolbarComponent extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleBackButtonClick = this.handleBackButtonClick.bind(this);
  }

  render() {
    const buttonClassNames = [
      'btn',
      'btn-secondary',
      'action',
      'font-icon-left-open-big',
      'toolbar__back-button',
      'btn--no-text',
    ];
    const backButtonProps = {
      className: buttonClassNames.join(' '),
      onClick: this.handleBackButtonClick,
      href: '#',
      type: 'button',
    };

    return (
      <div className="toolbar--north container-fluid">
        <div className="toolbar__navigation">
          {this.props.showBackButton &&
            <button {...backButtonProps}></button>
          }
          {this.props.children}
        </div>
      </div>
    );
  }

  /**
   * Event handler for the back button.
   *
   * @param {Object} event
   */
  handleBackButtonClick(event) {
    if (typeof this.props.handleBackButtonClick !== 'undefined') {
      this.props.handleBackButtonClick(event);
      return;
    }

    event.preventDefault();
    window.ss.router.back();
  }
}

ToolbarComponent.propTypes = {
  handleBackButtonClick: React.PropTypes.func,
  showBackButton: React.PropTypes.bool,
};

ToolbarComponent.defaultProps = {
  showBackButton: false,
};

export default ToolbarComponent;
