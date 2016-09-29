import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { Tab } from 'react-bootstrap-ss';

class TabItem extends SilverStripeComponent {
  /**
   * Fetches the properties for the tab content
   *
   * @returns {object} properties
   */
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
      unmountOnExit,
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
  tabClassName: React.PropTypes.string,
};

TabItem.defaultProps = {
  className: '',
  extraClass: '',
};

export default TabItem;
