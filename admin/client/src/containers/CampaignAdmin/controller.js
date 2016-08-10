import { withRouter } from 'react-router';
import ConfigHelpers from 'lib/Config';
import reactRouteRegister from 'lib/ReactRouteRegister';
import CampaignAdmin from './CampaignAdmin';

document.addEventListener('DOMContentLoaded', () => {
  const sectionConfig = ConfigHelpers.getSection('SilverStripe\\Admin\\CampaignAdmin');

  reactRouteRegister.add({
    path: sectionConfig.url,
    component: withRouter(CampaignAdmin),
    childRoutes: [
      { path: ':type/:id/:view', component: CampaignAdmin },
      { path: 'set/:id/:view', component: CampaignAdmin },
    ],
  });
});
