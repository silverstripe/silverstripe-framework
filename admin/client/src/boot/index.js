import $ from 'jQuery';
import { combineReducers, createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import createLogger from 'redux-logger';
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

function appBoot() {
  reducerRegister.add('config', ConfigReducer);
  reducerRegister.add('form', FormReducer);
  reducerRegister.add('schemas', SchemaReducer);
  reducerRegister.add('records', RecordsReducer);
  reducerRegister.add('campaign', CampaignReducer);
  reducerRegister.add('breadcrumbs', BreadcrumbsReducer);

  const initialState = {};
  const rootReducer = combineReducers(reducerRegister.getAll());

  // Combine middleware
  const middleware = [thunkMiddleware];
  if (window.ss.config.environment === 'dev') {
    middleware.push(createLogger());
  }
  const createStoreWithMiddleware = applyMiddleware(...middleware)(createStore);

  // TODO: The store needs to be passed into route callbacks on the route context.
  window.store = createStoreWithMiddleware(rootReducer, initialState);

  // Set the initial config state.
  window.store.dispatch(configActions.setConfig(window.ss.config));
}

// TODO: This should be using `window.onload` but isn't because
// Entwine hooks are being used to set up the <Provider>.
// `window.onload` happens AFTER these Entwine hooks which means
// the store is undefined when the <Provider> is constructed.
$('body').entwine({ onadd: () => appBoot() });
