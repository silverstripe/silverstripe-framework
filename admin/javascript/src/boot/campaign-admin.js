import reducerRegister from 'reducer-register';
import $ from 'jQuery';
import React from 'react';
import ReactDOM from 'react-dom';
import CampaignAdmin from '../sections/campaign-admin/controller';
import campaignsReducer from '../state/campaigns/reducer';

// TODO: Move this to the controller.
reducerRegister.add('campaigns', campaignsReducer);

$.entwine('ss', function ($) {

    $('.cms-content.CampaignAdmin').entwine({
        onadd: function () {
            ReactDOM.render(<CampaignAdmin />, this[0]);
        },

        onremove: function () {
            ReactDOM.unmountComponentAtNode(this[0]);
        }
    });

});
