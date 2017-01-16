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
import ApolloClient, { createNetworkInterface } from 'apollo-client';
import { printRequest } from 'apollo-client/transport/networkInterface';
import qs from 'qs';

// Sections
// eslint-disable-next-line no-unused-vars
import CampaignAdmin from 'containers/CampaignAdmin/controller';

import es6promise from 'es6-promise';
es6promise.polyfill();

function appBoot() {
  const baseUrl = Config.get('absoluteBaseUrl');
  const networkInterface = createNetworkInterface({
    uri: `${baseUrl}graphql/`,
    opts: {
      credentials: 'same-origin',
    },
  });
  const apolloClient = new ApolloClient({
    shouldBatch: true,
    addTypename: true,
    dataIdFromObject: (o) => {
      if (o.id >= 0 && o.__typename) {
        return `${o.__typename}:${o.id}`;
      }
      return null;
    },
    networkInterface,
  });

  networkInterface.use([{
    applyMiddleware(req, next) {
      const entries = printRequest(req.request);

      // eslint-disable-next-line no-param-reassign
      req.options.headers = Object.assign(
        {},
        req.options.headers,
        {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        }
      );
      // eslint-disable-next-line no-param-reassign
      req.options.body = qs.stringify(Object.assign(
        {},
        entries,
        { variables: JSON.stringify(entries.variables) }
      ));
      next();
    },
  }]);

  reducerRegister.add('config', ConfigReducer);
  reducerRegister.add('form', ReduxFormReducer);
  reducerRegister.add('schemas', SchemaReducer);
  reducerRegister.add('records', RecordsReducer);
  reducerRegister.add('campaign', CampaignReducer);
  reducerRegister.add('breadcrumbs', BreadcrumbsReducer);
  reducerRegister.add('routing', routerReducer);
  reducerRegister.add('apollo', apolloClient.reducer());

  bootInjector.start();

  const initialState = {};
  const rootReducer = combineReducers(reducerRegister.getAll());
  const middleware = [
    thunkMiddleware,
    apolloClient.middleware(),
  ];

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

  // Expose client for legacy use
  window.ss = window.ss || {};
  window.ss.apolloClient = apolloClient;

  // Bootstrap routing
  const routes = new BootRoutes(store, apolloClient);
  routes.start(window.location.pathname);

  // @TODO - Remove once we remove entwine
  // Enable top-level css selectors for react-dependant entwine sections
  if (window.jQuery) {
    window.jQuery('body').addClass('js-react-boot');
  }
}

window.onload = appBoot;
