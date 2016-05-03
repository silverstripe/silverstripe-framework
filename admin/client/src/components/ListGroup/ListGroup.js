import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import ListGroupItem from './ListGroupItem';

class ListGroup extends SilverStripeComponent {
  render() {
    return (
      <div className="list-group">
        {this.props.items.map(() => <ListGroupItem />)}
      </div>
    );
  }
}

ListGroup.propTypes = {
  items: React.PropTypes.array,
};

export default ListGroup;
