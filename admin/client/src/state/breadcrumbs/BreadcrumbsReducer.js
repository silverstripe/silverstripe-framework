import deepFreeze from 'deep-freeze';
import ACTION_TYPES from './BreadcrumbsActionTypes';

const initialState = deepFreeze({
  breadcrumbs: [],
});

function reducer(state = initialState, action) {
  switch (action.type) {

    case ACTION_TYPES.SET_BREADCRUMBS:
      return deepFreeze(Object.assign({}, state, {
        breadcrumbs: action.payload.breadcrumbs,
      }));

    default:
      return state;

  }
}

export default reducer;
