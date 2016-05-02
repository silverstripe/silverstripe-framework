import $ from 'jQuery';
import { combineReducers, createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import createLogger from 'redux-logger';
import ConfigHelpers from 'lib/Config';
import router from 'lib/Router';
import routeRegister from 'lib/RouteRegister';
import ReducerRegister from 'lib/ReducerRegister';
import * as configActions from 'state/config/ConfigActions';
import ConfigReducer from 'state/config/ConfigReducer';
import FormReducer from 'state/form/FormReducer';
import SchemaReducer from 'state/schema/SchemaReducer';
import RecordsReducer from 'state/records/RecordsReducer';
import CampaignReducer from 'state/campaign/CampaignReducer';
import BreadcrumbsReducer from 'state/breadcrumbs/BreadcrumbsReducer';

// Sections
// eslint-disable-next-line no-unused-vars
import CampaignAdmin from 'containers/CampaignAdmin/controller';

/*
 * We're assigning instances to the `ss` namespace because singletons only
 * work within the context on a single Browserify bundle.
 *
 * For example - assume the `lib` bundle exposes a singleton called `register`.
 * If bundle `a` imports `register`, as an external dependency, then all modules
 * in bundle `a` will get the same copy of `register` when importing it.
 *
 * Likewise if bundle `b` imports `register` as an external dependency, all modules
 * in bundle `b` will get the same copy of `register`.
 *
 * However the copy of `register` available to all modules in bundle `a` is not
 * the same copy of `register` that's available to modules in bundle `b`.
 * Singletons only work within the context of a Browserify bundle but not across bundles.
 *
 * @TODO Look into SystemJS as a solution https://github.com/systemjs/systemjs
 */
window.ss = window.ss || {};
window.ss.router = router;
window.ss.reducerRegister = new ReducerRegister();

function getBasePath() {
  const a = document.createElement('a');
  a.href = document.getElementsByTagName('base')[0].href;

  let basePath = a.pathname;

  // No trailing slash
  basePath = basePath.replace(/\/$/, '');

  // Mandatory leading slash
  if (basePath.match(/^[^\/]/)) {
    basePath = `/${basePath}`;
  }

  return basePath;
}

function appBoot() {
  reducerRegister.add('config', ConfigReducer);
  reducerRegister.add('form', FormReducer);
  reducerRegister.add('schemas', SchemaReducer);
  reducerRegister.add('records', RecordsReducer);
  reducerRegister.add('campaign', CampaignReducer);
  reducerRegister.add('breadcrumbs', BreadcrumbsReducer);

  const initialState = {};
  const rootReducer = combineReducers(window.ss.reducerRegister.getAll());
  const middleware = [thunkMiddleware];

  if (window.ss.config.environment === 'dev') {
    middleware.push(createLogger());
  }

  const createStoreWithMiddleware = applyMiddleware(...middleware)(createStore);
  const store = createStoreWithMiddleware(rootReducer, initialState);

  // Set the initial config state.
  store.dispatch(configActions.setConfig(window.ss.config));

  // Initialise routes
  router.base(getBasePath());

  router('*', (ctx, next) => {
    // eslint-disable-next-line no-param-reassign
    ctx.store = store;
    next();
  });

  // Register all top level routes.
  ConfigHelpers
    .getTopLevelRoutes()
    .forEach((route) => {
      routeRegister.add(`/${route}(/*?)?`, (ctx, next) => {
        if (document.readyState !== 'complete') {
          next();
          return;
        }

        // Load the panel then call the next route.
        $('.cms-container')
          .entwine('ss')
          .handleStateChange(null, ctx.state)
          .done(next);
      });
    });

  const registeredRoutes = routeRegister.getAll();

  for (const route in registeredRoutes) {
    if (registeredRoutes.hasOwnProperty(route)) {
      router(route, registeredRoutes[route]);
    }
  }

  router.start();

  // Clean up referneces to callbacks in the route register.
  routeRegister.removeAll();
}

window.onload = appBoot;
