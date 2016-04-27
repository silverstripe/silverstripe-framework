import $ from 'jQuery';
import { combineReducers, createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import createLogger from 'redux-logger';
import ConfigHelpers from 'lib/Config';
import router from 'lib/Router';
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
import CampaignAdmin from 'containers/CampaignAdmin/index';

window.ss = window.ss || {};
window.ss.router = router;

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

  if (window.ss.config.environment === 'dev') {
    middleware.push(createLogger());
  }

  const createStoreWithMiddleware = applyMiddleware(...middleware)(createStore);
  const store = window.store = createStoreWithMiddleware(rootReducer, initialState);

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
      router(`/${route}(/*?)?`, (ctx, next) => {
        // If the page isn't ready.
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

  router.start();
}

// TODO: This should be using `window.onload` but isn't because
// Entwine hooks are being used to set up the <Provider>.
// `window.onload` happens AFTER these Entwine hooks which means
// the store is undefined when the <Provider> is constructed.
$.entwine('ss', () => {
  $('body').entwine({ onadd: () => appBoot() });
});
