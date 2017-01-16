import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class Toolbar extends SilverStripeComponent {

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
      <div className="toolbar toolbar--north">
        <div className="toolbar__navigation fill-width">
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
  }
}

Toolbar.propTypes = {
  handleBackButtonClick: React.PropTypes.func,
  showBackButton: React.PropTypes.bool,
  breadcrumbs: React.PropTypes.array,
};

Toolbar.defaultProps = {
  showBackButton: false,
};

export default Toolbar;
