import BootRoutes from './BootRoutes';
import { combineReducers, createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import createLogger from 'redux-logger';
import Config from 'lib/Config';
import reducerRegister from 'lib/ReducerRegister';
import injector from 'lib/Injector';
import * as configActions from 'state/config/ConfigActions';
import ConfigReducer from 'state/config/ConfigReducer';
import FormReducer from 'state/form/FormReducer';
import SchemaReducer from 'state/schema/SchemaReducer';
import RecordsReducer from 'state/records/RecordsReducer';
import CampaignReducer from 'state/campaign/CampaignReducer';
import BreadcrumbsReducer from 'state/breadcrumbs/BreadcrumbsReducer';
import TextField from 'components/TextField/TextField';
import HiddenField from 'components/HiddenField/HiddenField';
import GridField from 'components/GridField/GridField';
import FormAction from 'components/FormAction/FormAction';
import PopoverField from 'components/PopoverField/PopoverField';
import { routerReducer } from 'react-router-redux';

// Sections
// eslint-disable-next-line no-unused-vars
import CampaignAdmin from 'containers/CampaignAdmin/controller';

function appBoot() {
  reducerRegister.add('config', ConfigReducer);
  reducerRegister.add('form', FormReducer);
  reducerRegister.add('schemas', SchemaReducer);
  reducerRegister.add('records', RecordsReducer);
  reducerRegister.add('campaign', CampaignReducer);
  reducerRegister.add('breadcrumbs', BreadcrumbsReducer);
  reducerRegister.add('routing', routerReducer);

  injector.register('TextField', TextField);
  injector.register('HiddenField', HiddenField);
  injector.register('GridField', GridField);
  injector.register('PopoverField', PopoverField);
  injector.register('FormAction', FormAction);

  const initialState = {};
  const rootReducer = combineReducers(reducerRegister.getAll());
  const middleware = [thunkMiddleware];

  const env = Config.get('environment');
  const debugging = Config.get('debugging');
  if (env === 'dev' && debugging) {
    middleware.push(createLogger());
  }

  const createStoreWithMiddleware = applyMiddleware(...middleware)(createStore);
  const store = createStoreWithMiddleware(rootReducer, initialState);

  // Set the initial config state.
  store.dispatch(configActions.setConfig(Config.getAll()));

  // Bootstrap routing
  const routes = new BootRoutes(store);
  routes.start(window.location.pathname);
}

window.onload = appBoot;
