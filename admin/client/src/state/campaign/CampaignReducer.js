import deepFreeze from 'deep-freeze-strict';
import ACTION_TYPES from './CampaignActionTypes';

const initialState = deepFreeze({
  campaignId: null,
  changeSetItemId: null,
  isPublishing: false,
  view: null,
});

function reducer(state = initialState, action) {
  switch (action.type) {

    case ACTION_TYPES.SET_CAMPAIGN_SELECTED_CHANGESETITEM:
      return deepFreeze(Object.assign({}, state, {
        changeSetItemId: action.payload.changeSetItemId,
      }));

    case ACTION_TYPES.SET_CAMPAIGN_ACTIVE_CHANGESET:
      return deepFreeze(Object.assign({}, state, {
        campaignId: action.payload.campaignId,
        view: action.payload.view,
        changeSetItemId: null,
      }));

    case ACTION_TYPES.PUBLISH_CAMPAIGN_REQUEST:
      return deepFreeze(Object.assign({}, state, {
        isPublishing: true,
      }));

    case ACTION_TYPES.PUBLISH_CAMPAIGN_SUCCESS:
    case ACTION_TYPES.PUBLISH_CAMPAIGN_FAILURE:
      return deepFreeze(Object.assign({}, state, {
        isPublishing: false,
      }));

    default:
      return state;

  }
}

export default reducer;
