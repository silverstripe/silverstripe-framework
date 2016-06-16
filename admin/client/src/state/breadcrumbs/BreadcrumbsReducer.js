import deepFreeze from 'deep-freeze-strict';
import ACTION_TYPES from './BreadcrumbsActionTypes';

const initialState = deepFreeze([]);

function reducer(state = initialState, action) {
  switch (action.type) {

    case ACTION_TYPES.SET_BREADCRUMBS:
      return deepFreeze(Object.assign([], action.payload.breadcrumbs));

    default:
      return state;

  }
}

export default reducer;
