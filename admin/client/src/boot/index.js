import $ from 'jQuery';
import ReactDOM from 'react-dom';
import { combineReducers, createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import createLogger from 'redux-logger';
import Config from 'lib/Config';
import router from 'lib/Router';
import routeRegister from 'lib/RouteRegister';
import reducerRegister from 'lib/ReducerRegister';
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
  const rootReducer = combineReducers(reducerRegister.getAll());
  const middleware = [thunkMiddleware];

  if (Config.get('environment') === 'dev') {
    middleware.push(createLogger());
  }

  const createStoreWithMiddleware = applyMiddleware(...middleware)(createStore);
  const store = createStoreWithMiddleware(rootReducer, initialState);

  // Set the initial config state.
  store.dispatch(configActions.setConfig(Config.getAll()));

  // Initialise routes
  router.base(getBasePath());

  router('*', (ctx, next) => {
    // eslint-disable-next-line no-param-reassign
    ctx.store = store;
    next();
  });

  router.exit('*', (ctx, next) => {
    ReactDOM.unmountComponentAtNode(document.getElementsByClassName('cms-content')[0]);
    next();
  });

  /*
   * Register all top level routes.
   * This can be removed when top level sections are converted to React,
   * have their own JavaScript controllers, and register their own routes.
   */
  const sections = Config.get('sections');
  Object.keys(sections).forEach((key) => {
    const sectionConfig = sections[key];

    // Skip react routes which are handled by individual route setup
    if (sectionConfig.reactRoute) {
      return;
    }

    let route = sectionConfig.route;
    route = route.replace(/\/$/, ''); // Remove trailing slash
    route = `/${route}(/*?)?`; // add optional trailing slash
    routeRegister.add(route, (ctx, next) => {
      if (document.readyState !== 'complete' || ctx.init) {
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
}

window.onload = appBoot;
