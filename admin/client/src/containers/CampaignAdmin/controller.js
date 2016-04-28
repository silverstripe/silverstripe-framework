import routeRegister from '../../lib/RouteRegister';
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import ConfigHelpers from '../../lib/Config';
import CampaignAdmin from './CampaignAdmin';
import * as CampaignActions from 'state/campaign/CampaignActions';

document.addEventListener('DOMContentLoaded', () => {
  const config = ConfigHelpers.getSection('CampaignAdmin');
  const baseRoute = window.ss.router.resolveURLToBase(config.route);
  const viewRoute = window.ss.router.resolveURLToBase(config.campaignViewRoute);

  routeRegister.add(`${baseRoute}*`, (ctx, next) => {
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
