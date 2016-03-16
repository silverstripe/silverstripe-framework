import { combineReducers, createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import createLogger from 'redux-logger';
import reducerRegister from 'reducer-register';

function appBoot() {
    const initialState = {};
    const rootReducer = combineReducers(reducerRegister.getAll());
    const createStoreWithMiddleware = applyMiddleware(thunkMiddleware, createLogger())(createStore);
    const store = createStoreWithMiddleware(rootReducer, initialState);

    console.log(store.getState()); 
}

window.onload = appBoot;
