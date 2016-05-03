import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class ListGroupItem extends SilverStripeComponent {

  constructor(props) {
    super(props);
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    let className = `list-group-item ${this.props.className}`;
    return (
      <a tabIndex="0" className={className} onClick={this.handleClick}>
        {this.props.children}
      </a>
    );
  }

  handleClick(event) {
    if (this.props.handleClick) {
      this.props.handleClick(event, this.props.handleClickArg);
    }
  }
}

ListGroupItem.propTypes = {
  handleClickArg: React.PropTypes.any,
  handleClick: React.PropTypes.func,
};

export default ListGroupItem;
