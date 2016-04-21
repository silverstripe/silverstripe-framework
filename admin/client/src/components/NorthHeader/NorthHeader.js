import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class NorthHeader extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleBackButtonClick = this.handleBackButtonClick.bind(this);
  }

  render() {
    const buttonClassNames = [
      'btn',
      'btn-secondary',
      'action',
      'font-icon-left-open',
      'toolbar__navigation__back-button',
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

NorthHeader.propTypes = {
  handleBackButtonClick: React.PropTypes.func,
  showBackButton: React.PropTypes.bool,
};

NorthHeader.defaultProps = {
  showBackButton: false,
};

export default NorthHeader;
