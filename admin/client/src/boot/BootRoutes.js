import $ from 'jQuery';
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { Router as ReactRouter, useRouterHistory } from 'react-router';
import createHistory from 'history/lib/createBrowserHistory';
import Config from 'lib/Config';
import pageRouter from 'lib/Router';
import reactRouteRegister from 'lib/ReactRouteRegister';
import App from 'containers/App/App';
import { syncHistoryWithStore } from 'react-router-redux';

/**
 * Bootstraps routes
 */
class BootRoutes {

  /**
   * @param {Object} store Redux store
   */
  constructor(store) {
    this.store = store;

    // pageRouter must be initialised, regardless of whether we are
    // using page.js routing for this request.
    const base = Config.get('absoluteBaseUrl');
    pageRouter.setAbsoluteBase(base);
  }

  /**
   * Conditionally registers routes either as legacy (via page.js) or react-route powered routes
   *
   * @param {String} location Current location to check
   */
  start(location) {
    // Decide which router to use
    if (this.matchesLegacyRoute(location)) {
      this.initLegacyRouter();
    } else {
      this.initReactRouter();
    }
  }

  /**
   * Determine if the given location matches a legacy or a react route.
   *
   * @param {String} location URL
   * @return {Boolean} True if this is a legacy non-react route
   */
  matchesLegacyRoute(location) {
    // Legacy routes will always cause a full page reload
    const sections = Config.get('sections');
    const currentPath = pageRouter.resolveURLToBase(location).replace(/\/$/, '');

    // Check if the current url matches a legacy route
    return !!Object.keys(sections).find((key) => {
      const section = sections[key];
      const route = pageRouter.resolveURLToBase(section.url).replace(/\/$/, '');

      // Skip react routes
      if (section.reactRouter) {
        return false;
      }

      // Check if the beginning of the route is the same as the current location.
      // Since we haven't decided on a router yet, we can't use it for route matching.
      // TODO Limit full page load when transitioning from legacy to react route or vice versa
      return currentPath.match(route);
    });
  }

  /**
   * Initialise routing to use react-route powered routing
   */
  initReactRouter() {
    reactRouteRegister.updateRootRoute({
      component: App,
    });
    let history = syncHistoryWithStore(
      useRouterHistory(createHistory)({
        basename: Config.get('baseUrl'),
      }),
      this.store
    );
    ReactDOM.render(
      <Provider store={this.store}>
        <ReactRouter
          history={history}
          routes={reactRouteRegister.getRootRoute()}
        />
      </Provider>,
      document.getElementsByClassName('cms-content')[0]
    );
  }

  /**
   * Initialise routing to use page.js powered legacy routing for non-react sections
   */
  initLegacyRouter() {
    const sections = Config.get('sections');
    const store = this.store;

    pageRouter('*', (ctx, next) => {
      // eslint-disable-next-line no-param-reassign
      ctx.store = store;
      next();
    });

    // Register all top level routes.
    // This can be removed when top level sections are converted to React,
    // have their own JavaScript controllers, and register their own routes.
    Object.keys(sections).forEach((key) => {
      let route = pageRouter.resolveURLToBase(sections[key].url);
      route = route.replace(/\/$/, ''); // Remove trailing slash
      route = `${route}(/*?)?`; // add optional trailing slash

      // page.js based routing, excludes any React-powered sections
      pageRouter(route, (ctx, next) => {
        if (document.readyState !== 'complete' || ctx.init) {
          next();
          return;
        }

        // Load the panel and stop processing routes.
        $('.cms-container')
          .entwine('ss')
          .handleStateChange(null, ctx.state);
      });
    });

    pageRouter.start();
  }
}

export default BootRoutes;
