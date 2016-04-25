import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import backend from 'lib/Backend';
import * as actions from 'state/campaign/CampaignActions';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormAction from 'components/FormAction/FormAction';
import i18n from 'i18n';
import NorthHeader from 'components/NorthHeader/NorthHeader';
import FormBuilder from 'components/FormBuilder/FormBuilder';
import CampaignAdminList from './CampaignAdminList';

class CampaignAdmin extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.addCampaign = this.addCampaign.bind(this);
    this.createFn = this.createFn.bind(this);
    this.publishApi = backend.createEndpointFetcher({
      url: this.props.sectionConfig.publishEndpoint.url,
      method: this.props.sectionConfig.publishEndpoint.method,
      defaultData: { SecurityID: this.props.config.SecurityID },
      payloadSchema: {
        id: { urlReplacement: ':id', remove: true },
      },
    });
  }

  componentDidMount() {
    super.componentDidMount();
    // While a component is mounted it will intercept all routes and handle internally
    let captureRoute = true;
    const route = window.ss.router.resolveURLToBase(this.props.sectionConfig.campaignViewRoute);

    // Capture routing within this section
    window.ss.router(route, (ctx, next) => {
      if (captureRoute) {
        // If this component is mounted, then handle all page changes via
        // state / redux
        this.props.actions.showCampaignView(ctx.params.id, ctx.params.view);
      } else {
        // If component is not mounted, we need to allow root routes to load
        // this section in via ajax
        next();
      }
    });

    // When leaving this section to go to another top level section then
    // disable route capturing.
    window.ss.router.exit(route, (ctx, next) => {
      const applies = window.ss.router.routeAppliesToCurrentLocation(route);
      if (!applies) {
        captureRoute = false;
  }
      next();
    });
  }

  render() {
    let view = null;

    switch (this.props.view) {
      case 'show':
        view = this.renderItemListView();
        break;
      case 'edit':
        view = this.renderDetailEditView();
        break;
      default:
        view = this.renderIndexView();
    }

    return view;
  }

  /**
   * Renders the default view which displays a list of Campaigns.
   *
   * @return object
   */
  renderIndexView() {
    const schemaUrl = this.props.sectionConfig.forms.editForm.schemaUrl;

    return (
      <div className="cms-content__inner no-preview">
        <div className="cms-content__left cms-campaigns collapse in" aria-expanded="true">
          <NorthHeader>
            <div className="breadcrumb breadcrumb--current-only">
              <h2 className="breadcrumb__item-title breadcrumb__item-title--last">Campaigns</h2>
            </div>
          </NorthHeader>
          <div className="panel-scrollable--single-toolbar">
            <div className="toolbar--content">
              <div className="btn-toolbar">
          <FormAction
            label={i18n._t('Campaigns.ADDCAMPAIGN')}
                  icon={'plus'}
            handleClick={this.addCampaign}
          />
              </div>
            </div>
          <FormBuilder schemaUrl={schemaUrl} createFn={this.createFn} />
        </div>
      </div>
      </div>
    );
  }

  /**
   * Renders a list of items in a Campaign.
   *
   * @return object
   */
  renderItemListView() {
    const props = {
      campaignId: this.props.campaignId,
      itemListViewEndpoint: this.props.sectionConfig.itemListViewEndpoint,
      publishApi: this.publishApi,
      breadcrumbs: this.getBreadcrumbs(),
    };

    return (
      <CampaignAdminList {...props} />
    );
  }

  /**
   * @todo
   */
  renderDetailEditView() {
    return <p>Edit</p>;
  }

  /**
   * Hook to allow customisation of components being constructed by FormBuilder.
   *
   * @param object Component - Component constructor.
   * @param object props - Props passed from FormBuilder.
   *
   * @return object - Instanciated React component
   */
  createFn(Component, props) {
    const campaignViewRoute = this.props.sectionConfig.campaignViewRoute;

    if (props.component === 'GridField') {
      const extendedProps = Object.assign({}, props, {
        data: Object.assign({}, props.data, {
          handleDrillDown: (event, record) => {
            // Set url and set list
            const path = campaignViewRoute
              .replace(/:type\?/, 'set')
              .replace(/:id\?/, record.ID)
              .replace(/:view\?/, 'show');

            window.ss.router.show(path);
          },
        }),
      });

      return <Component key={extendedProps.name} {...extendedProps} />;
    }

    return <Component key={props.name} {...props} />;
  }

  /**
   * @todo Use dynamic breadcrumbs
   */
  getBreadcrumbs() {
    return [
      {
        text: 'Campaigns',
        href: 'admin/campaigns',
      },
      {
        text: 'March release',
        href: 'admin/campaigns/show/1',
      },
    ];
  }

  /**
   * Gets preview URL for itemid
   * @param int id
   * @returns string
   */
  previewURLForItem(id) {
    if (!id) {
      return '';
    }

    // hard code in baseurl for any itemid preview url
    return document.getElementsByTagName('base')[0].href;
  }

  addCampaign() {
    // Add campaign
  }

}

CampaignAdmin.propTypes = {
  sectionConfig: React.PropTypes.shape({
    forms: React.PropTypes.shape({
      editForm: React.PropTypes.shape({
        schemaUrl: React.PropTypes.string,
      }),
    }),
  }),
  config: React.PropTypes.shape({
    SecurityID: React.PropTypes.string,
  }),
  sectionConfigKey: React.PropTypes.string.isRequired,
};

function mapStateToProps(state, ownProps) {
  return {
    config: state.config,
    sectionConfig: state.config.sections[ownProps.sectionConfigKey],
    campaignId: state.campaign.campaignId,
    view: state.campaign.view,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    actions: bindActionCreators(actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(CampaignAdmin);
