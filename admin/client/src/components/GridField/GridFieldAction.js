import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class GridFieldAction extends SilverStripeComponent {
  constructor(props) {
    super(props);
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    return (
      <button
        className={`grid-field__icon-action font-icon-${this.props.icon} btn--icon-large`}
        onClick={this.handleClick}
      />
    );
  }

  handleClick(event) {
    this.props.handleClick(event, this.props.record.ID);
  }
}

GridFieldAction.PropTypes = {
  handleClick: React.PropTypes.func.isRequired,
};

export default GridFieldAction;
