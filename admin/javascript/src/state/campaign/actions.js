import ACTION_TYPES from './action-types';

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

