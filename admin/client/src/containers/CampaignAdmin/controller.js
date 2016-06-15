import $ from 'jQuery';
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import CampaignAdmin from './CampaignAdmin';
import Config from 'lib/Config';
import * as CampaignActions from 'state/campaign/CampaignActions';
import router from 'lib/Router';
import routeRegister from 'lib/RouteRegister';

document.addEventListener('DOMContentLoaded', () => {
  const sections = Config.get('sections');
  const sectionConfig = sections.CampaignAdmin;
  const viewRoute = router.resolveURLToBase(sectionConfig.campaignViewRoute);

  // Emulate behaviour in getTopLevelRoutes() and boot/index.js
  let route = router.resolveURLToBase(sectionConfig.route);
  route = route.replace(/\/$/, ''); // remove trailing slash
  route = `${route}(/*?)?`; // add optional trailing slash
  routeRegister.add(route, (ctx, next) => {
    // We have to manually select the section menu item because the legacy
    // implementation depends on a PJAX response to select the correct menu item.
    // See `updateMenuFromResponse` in `/admin/client/src/legacy/LeftAndMain.Menu.js`
    // This can be removed when we refactor the menu to a React component.
    $('#Menu-CampaignAdmin').entwine('ss').select();

    const securityId = Config.get('SecurityID');
    ReactDOM.render(
      <Provider store={ctx.store}>
        <CampaignAdmin sectionConfig={sectionConfig} securityId={securityId} />
      </Provider>
      , document.getElementsByClassName('cms-content')[0]
    );
    next();
  });

  routeRegister.add(viewRoute, (ctx) => {
    CampaignActions.showCampaignView(ctx.params.id, ctx.params.view)(ctx.store.dispatch);
  });
});
