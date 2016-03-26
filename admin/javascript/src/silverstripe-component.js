/**
 * @file Base component which all SilverStripe ReactJS components should extend from.
 */

import React, { PropTypes, Component } from 'react';
import $ from '../../../javascript/src/jQuery';

class SilverStripeComponent extends Component {

	/**
	 * @func componentDidMount
	 * @desc Bind event listeners which are triggered by legacy-land JavaScript.
	 * This lets us update the component when something happens in the outside world.
	 */
	componentDidMount() {
		if (typeof this.props.cmsEvents === 'undefined') {
			return;
		}

		// Save some props for later. When we come to unbind these listeners
		// there's no guarantee these props will be the same or even present.
		this.cmsEvents = this.props.cmsEvents;

		for (let cmsEvent in this.cmsEvents) {
			$(document).on(cmsEvent, this.cmsEvents[cmsEvent].bind(this));
		}
	}

	/**
	 * @func componentWillUnmount
	 * @desc Unbind the event listeners we added in componentDidMount.
	 */
	componentWillUnmount() {
		for (let cmsEvent in this.cmsEvents) {
			$(document).off(cmsEvent);
		}
	}

	/**
	 * @func emitCmsEvent
	 * @param string componentEvent - Namespace component event e.g. 'my-component.title-changed'.
	 * @param object|string|array|number [data] - Some data to pass with the event.
	 * @desc Notifies legacy-land something has changed within our component.
	 */
	emitCmsEvent(componentEvent, data) {
		$(document).trigger(componentEvent, data);
	}

}

SilverStripeComponent.propTypes = {
	'cmsEvents': React.PropTypes.object
};

export default SilverStripeComponent;
