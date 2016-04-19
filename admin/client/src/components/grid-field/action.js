import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldActionComponent extends SilverStripeComponent {
  constructor(props) {
    super(props);
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    return (
      <button
        className={`grid-field__icon-action font-icon-${this.props.icon}`}
        onClick={this.handleClick}
      />
    );
  }

  handleClick(event) {
    this.props.handleClick(event, this.props.record.ID);
  }
}

GridFieldActionComponent.PropTypes = {
  handleClick: React.PropTypes.func.isRequired,
};

export default GridFieldActionComponent;
