/**	
 * @file Base component which all SilverStripe ReactJS components should extend from.
 */

import React, { PropTypes, Component } from 'react';
import $ from '../../../javascript/src/jQuery';

class SilverStripeComponent extends Component {

	constructor(props) {
		super(props);

		// Setup component routing.
		if (typeof this.props.route !== 'undefined') {
			// The component's render method gets switched based on the current path.
			// If the current path matches the component's route, the component is displayed.
			// Otherwise the component's render method returns null, resulting in the component not rendering.
			this._render = this.render;

			this.render = () => {
				var component = null;

				if (this.isComponentRoute()) {
					component = this._render();
				}

				return component;
			};

			window.ss.router(this.props.route, (ctx, next) => {
				this.handleEnterRoute(ctx, next);
			});
			window.ss.router.exit(this.props.route, (ctx, next) => {
				this.handleExitRoute(ctx, next);
			});
		}
	}

	componentDidMount() {
		if (typeof this.props.cmsEvents === 'undefined') {
			return;
		}

		// Save some props for later. When we come to unbind these listeners
		// there's no guarantee these props will be the same or even present.
		this.cmsEvents = this.props.cmsEvents;

		// Bind event listeners which are triggered by legacy-land JavaScript.
		// This lets us update the component when something happens in the outside world.
		for (let cmsEvent in this.cmsEvents) {
			$(document).on(cmsEvent, this.cmsEvents[cmsEvent].bind(this));
		}
	}

	componentWillUnmount() {
		// Unbind the event listeners we added in componentDidMount.
		for (let cmsEvent in this.cmsEvents) {
			$(document).off(cmsEvent);
		}
	}

	handleEnterRoute(ctx, next) {
		next();
	}

	handleExitRoute(ctx, next) {
		next();
	}

	/**
	 * Checks if the component should be rended on the current path.
	 *
	 * @param object [params] - If a params object is passed in it's mutated by page.js to contains route parans like ':id'.
	 */
	isComponentRoute(params = {}) {
		if (typeof this.props.route === 'undefined') {
			return true;
		}

		let route = new window.ss.router.Route(this.props.route);

		return route.match(window.ss.router.current, params);
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
	'cmsEvents': React.PropTypes.object
};

export default SilverStripeComponent;
