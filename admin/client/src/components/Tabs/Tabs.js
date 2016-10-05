import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { Tab, Nav, NavItem } from 'react-bootstrap-ss';

class Tabs extends SilverStripeComponent {
  /**
   * Returns props for the container component
   *
   * @returns {object}
   */
  getContainerProps() {
    const {
      activeKey,
      onSelect,
      className,
      extraClass,
      id,
      } = this.props;
    const combinedClassName = `${className} ${extraClass}`;

    return {
      activeKey,
      className: combinedClassName,
      defaultActiveKey: this.getDefaultActiveKey(),
      onSelect,
      id,
    };
  }

  /**
   * Determines a default tab to be opened and validates the given default tab.
   * Replaces the given default tab if it is invalid with a valid tab.
   *
   * @returns {string}
   */
  getDefaultActiveKey() {
    let active = null;

    if (typeof this.props.defaultActiveKey === 'string') {
      const activeChild = React.Children.toArray(this.props.children)
        .find((child) => child.props.name === this.props.defaultActiveKey);

      if (activeChild) {
        active = activeChild.props.name;
      }
    }

    if (typeof active !== 'string') {
      React.Children.forEach(this.props.children, (child) => {
        if (typeof active !== 'string') {
          active = child.props.name;
        }
      });
    }

    return active;
  }

  /**
   * Render an individual link for the tabset
   *
   * @param {object} child
   * @returns {Component}
   */
  renderTab(child) {
    if (child.props.title === null) {
      return null;
    }
    return (
      <NavItem eventKey={child.props.name}
        disabled={child.props.disabled}
        className={child.props.tabClassName}
      >
        {child.props.title}
      </NavItem>
    );
  }

  /**
   * Builds the tabset navigation links, will hide the links if there is only one child
   *
   * @returns {Component}
   */
  renderNav() {
    const tabs = React.Children
      .map(this.props.children, this.renderTab);

    if (tabs.length <= 1) {
      return null;
    }

    return (
      <Nav bsStyle={this.props.bsStyle} role="tablist">
        {tabs}
      </Nav>
    );
  }

  render() {
    const containerProps = this.getContainerProps();
    const nav = this.renderNav();

    return (
      <Tab.Container {...containerProps}>
        <div className="wrapper">
          { nav }
          <Tab.Content animation={this.props.animation}>
            {this.props.children}
          </Tab.Content>
        </div>
      </Tab.Container>
    );
  }
}

Tabs.propTypes = {
  id: React.PropTypes.string.isRequired,
  defaultActiveKey: React.PropTypes.string,
  extraClass: React.PropTypes.string,
};

Tabs.defaultProps = {
  bsStyle: 'tabs',
  className: '',
  extraClass: '',
};

export default Tabs;
