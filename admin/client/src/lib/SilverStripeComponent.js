/**
 * @file Base component which all SilverStripe ReactJS components should extend from.
 */

import React, { Component } from 'react';
import $ from 'jQuery';

class SilverStripeComponent extends Component {

  componentDidMount() {
    if (typeof this.props.cmsEvents === 'undefined') {
      return;
    }

    // Save some props for later. When we come to unbind these listeners
    // there's no guarantee these props will be the same or even present.
    this.cmsEvents = this.props.cmsEvents;

    // Bind event listeners which are triggered by legacy-land JavaScript.
    // This lets us update the component when something happens in the outside world.
    for (const cmsEvent in this.cmsEvents) {
      if ({}.hasOwnProperty.call(this.cmsEvents, cmsEvent)) {
        $(document).on(cmsEvent, this.cmsEvents[cmsEvent].bind(this));
      }
    }
  }

  componentWillUnmount() {
    // Unbind the event listeners we added in componentDidMount.
    for (const cmsEvent in this.cmsEvents) {
      if ({}.hasOwnProperty.call(this.cmsEvents, cmsEvent)) {
        $(document).off(cmsEvent);
      }
    }
  }

  /**
   * Notifies legacy-land something has changed within our component.
   *
   * @param string componentEvent - Namespace component event e.g. 'my-component.title-changed'.
   * @param object|string|array|number [data] - Some data to pass with the event.
   */
  emitCmsEvent(componentEvent, data) {
    $(document).trigger(componentEvent, data);
  }

}

SilverStripeComponent.propTypes = {
  cmsEvents: React.PropTypes.object,
};

export default SilverStripeComponent;
