import $ from 'jQuery';
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import CampaignAdmin from './controller';

// eslint-disable-next-line no-shadow
$.entwine('ss', ($) => {
  $('.cms-content.CampaignAdmin').entwine({
    onadd() {
      ReactDOM.render(
        <Provider store={window.store}>
          <CampaignAdmin sectionConfigKey="CampaignAdmin" />
        </Provider>
      , this[0]);
    },

    onremove() {
      ReactDOM.unmountComponentAtNode(this[0]);
    },
  });
});
