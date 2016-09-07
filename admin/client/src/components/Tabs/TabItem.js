import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { Tab } from 'react-bootstrap-ss';

class TabItem extends SilverStripeComponent {
  getTabProps() {
    const {
      name,
      className,
      extraClass,
      disabled,
      bsClass,
      onEnter,
      onEntering,
      onEntered,
      onExit,
      onExiting,
      onExited,
      animation,
      id,
      unmountOnExit,
      } = this.props;

    return {
      eventKey: name,
      className: `${className} ${extraClass}`,
      disabled,
      bsClass,
      onEnter,
      onEntering,
      onEntered,
      onExit,
      onExiting,
      onExited,
      animation,
      id,
      unmountOnExit,
      'aria-labelledby': this.props['aria-labelledby'],
    };
  }

  render() {
    const tabProps = this.getTabProps();
    return (
      <Tab.Pane {...tabProps}>
        {this.props.children}
      </Tab.Pane>
    );
  }
}

TabItem.propTypes = {
  name: React.PropTypes.string.isRequired,
  extraClass: React.PropTypes.string,
};

TabItem.defaultProps = {
  className: '',
  extraClass: '',
};

export default TabItem;
