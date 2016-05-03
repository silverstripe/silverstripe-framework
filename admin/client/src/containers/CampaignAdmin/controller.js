import $ from 'jQuery';
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import ConfigHelpers from 'lib/Config';
import CampaignAdmin from './CampaignAdmin';
import * as CampaignActions from 'state/campaign/CampaignActions';
import router from 'lib/Router';
import routeRegister from 'lib/RouteRegister';

document.addEventListener('DOMContentLoaded', () => {
  const config = ConfigHelpers.getSection('CampaignAdmin');
  const baseRoute = router.resolveURLToBase(config.route);
  const viewRoute = router.resolveURLToBase(config.campaignViewRoute);

  routeRegister.add(`${baseRoute}*`, (ctx, next) => {
    // We have to manually select the section menu item because the legacy
    // implementation depends on a PJAX response to select the correct menu item.
    // See `updateMenuFromResponse` in `/admin/client/src/legacy/LeftAndMain.Menu.js`
    // This can be removed when we refactor the menu to a React component.
    $('#Menu-CampaignAdmin').entwine('ss').select();

    ReactDOM.render(
      <Provider store={ctx.store}>
        <CampaignAdmin sectionConfig={config} securityId={window.ss.config.SecurityID} />
      </Provider>
      , document.getElementsByClassName('cms-content')[0]
    );
    next();
  });

  routeRegister.add(viewRoute, (ctx) => {
    CampaignActions.showCampaignView(ctx.params.id, ctx.params.view)(ctx.store.dispatch);
  });
});
