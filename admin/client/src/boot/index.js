import BootRoutes from './BootRoutes';
import { combineReducers, createStore, applyMiddleware, compose } from 'redux';
import thunkMiddleware from 'redux-thunk';
import { reducer as ReduxFormReducer } from 'redux-form';
import { routerReducer } from 'react-router-redux';
import Config from 'lib/Config';
import reducerRegister from 'lib/ReducerRegister';
import * as configActions from 'state/config/ConfigActions';
import ConfigReducer from 'state/config/ConfigReducer';
import SchemaReducer from 'state/schema/SchemaReducer';
import RecordsReducer from 'state/records/RecordsReducer';
import CampaignReducer from 'state/campaign/CampaignReducer';
import BreadcrumbsReducer from 'state/breadcrumbs/BreadcrumbsReducer';
import bootInjector from 'boot/BootInjector';

// Sections
// eslint-disable-next-line no-unused-vars
import CampaignAdmin from 'containers/CampaignAdmin/controller';

import es6promise from 'es6-promise';
es6promise.polyfill();

function appBoot() {
  reducerRegister.add('config', ConfigReducer);
  reducerRegister.add('form', ReduxFormReducer);
  reducerRegister.add('schemas', SchemaReducer);
  reducerRegister.add('records', RecordsReducer);
  reducerRegister.add('campaign', CampaignReducer);
  reducerRegister.add('breadcrumbs', BreadcrumbsReducer);
  reducerRegister.add('routing', routerReducer);

  bootInjector.start();

  const initialState = {};
  const rootReducer = combineReducers(reducerRegister.getAll());
  const middleware = [thunkMiddleware];

  const env = Config.get('environment');
  const debugging = Config.get('debugging');
  let runMiddleware = applyMiddleware(...middleware);

  // use browser extension `compose` function if it's available
  const composeExtension = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__;
  // use browser extension devTools if it's available
  // this is the old way: `devToolsExtension` is being deprecated
  const devTools = window.__REDUX_DEVTOOLS_EXTENSION__ || window.devToolsExtension;

  if (env === 'dev' && debugging) {
    if (typeof composeExtension === 'function') {
      // use compose from extension first
      runMiddleware = composeExtension(applyMiddleware(...middleware));
    } else if (typeof devTools === 'function') {
      // fallback to old way
      runMiddleware = compose(applyMiddleware(...middleware), devTools());
    }
  }

  const createStoreWithMiddleware = runMiddleware(createStore);
  const store = createStoreWithMiddleware(rootReducer, initialState);

  // Set the initial config state.
  store.dispatch(configActions.setConfig(Config.getAll()));

  // Expose store for legacy use
  window.ss = window.ss || {};
  window.ss.store = store;

  // Bootstrap routing
  const routes = new BootRoutes(store);
  routes.start(window.location.pathname);

  // @TODO - Remove once we remove entwine
  // Enable top-level css selectors for react-dependant entwine sections
  window.jQuery('body').addClass('react-boot');
}

window.onload = appBoot;
