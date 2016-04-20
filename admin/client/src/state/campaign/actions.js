import ACTION_TYPES from './action-types';
import RECORD_ACTION_TYPES from 'state/records/action-types';

/**
 * Show specified campaign set
 *
 * @param number campaignId - ID of the Campaign to show.
 * @param string view - The view mode to display the Campaign in.
 */
export function showCampaignView(campaignId, view) {
  return {
    type: ACTION_TYPES.SET_CAMPAIGN_ACTIVE_CHANGESET,
    payload: { campaignId, view },
  };
}

/**
 * Publish a campaign and all its items
 *
 * @param {Function} publishApi See silverstripe-backend.js
 * @param {number} campaignId
 * @return {Object}
 */
export function publishCampaign(publishApi, campaignId) {
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.PUBLISH_CAMPAIGN_REQUEST,
      payload: { campaignId },
    });

    publishApi({ id: campaignId })
      .then((data) => {
        dispatch({
          type: ACTION_TYPES.PUBLISH_CAMPAIGN_SUCCESS,
          payload: { campaignId },
        });
        dispatch({
          type: RECORD_ACTION_TYPES.FETCH_RECORD_SUCCESS,
          payload: { recordType: 'ChangeSet', data },
        });
      })
      .catch((error) => {
        dispatch({
          type: ACTION_TYPES.PUBLISH_CAMPAIGN_FAILURE,
          payload: { error },
        });
      });
  };
}
