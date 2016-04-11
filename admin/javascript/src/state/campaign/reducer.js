import deepFreeze from 'deep-freeze';
import ACTION_TYPES from './action-types';

const initialState = {
  campaignId: null,
  view: null,
};

function campaignReducer(state = initialState, action) {
  switch (action.type) {

    case ACTION_TYPES.SET_CAMPAIGN_ACTIVE_CHANGESET:
      return deepFreeze(Object.assign({}, state, {
        campaignId: action.payload.campaignId,
        view: action.payload.view,
      }));

    default:
      return state;

  }
}

export default campaignReducer;
