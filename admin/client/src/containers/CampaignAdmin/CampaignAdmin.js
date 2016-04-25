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
    this.publishApi = backend.createEndpointFetcher({
      url: this.props.sectionConfig.publishEndpoint.url,
      method: this.props.sectionConfig.publishEndpoint.method,
      defaultData: { SecurityID: this.props.config.SecurityID },
      payloadSchema: {
        id: { urlReplacement: ':id', remove: true },
      },
    });
    this.campaignListCreateFn = this.campaignListCreateFn.bind(this);
    this.campaignEditCreateFn = this.campaignEditCreateFn.bind(this);
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
    const schemaUrl = this.props.sectionConfig.forms.EditForm.schemaUrl;
    const formActionProps = {
      label: i18n._t('Campaigns.ADDCAMPAIGN'),
      icon: 'plus',
      handleClick: this.addCampaign,
    };
    const formBuilderProps = {
      createFn: this.campaignListCreateFn,
      formId: 'EditForm',
      schemaUrl,
    };

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
                <FormAction {...formActionProps} />
              </div>
            </div>
            <FormBuilder {...formBuilderProps} />
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
   * Renders the Detail Edit Form for a Campaign.
   */
  renderDetailEditView() {
    const baseSchemaUrl = this.props.sectionConfig.forms.DetailEditForm.schemaUrl;
    const formBuilderProps = {
      createFn: this.campaignEditCreateFn,
      formId: 'DetailEditForm',
      schemaUrl: `${baseSchemaUrl}/ChangeSet/${this.props.campaignId}`,
    };

    return (
      <div className="cms-middle no-preview">
        <div className="cms-campaigns collapse in" aria-expanded="true">
          <NorthHeader showBackButton>
            <div className="breadcrumb breadcrumb--current-only">
              <h2 className="text-truncate toolbar__heading">Campaigns</h2>
            </div>
          </NorthHeader>
          <FormBuilder {...formBuilderProps} />
        </div>
      </div>
    );
  }

  /**
   * Hook to allow customisation of components being constructed
   * by the Campaign list FormBuilder.
   *
   * @param object Component - Component constructor.
   * @param object props - Props passed from FormBuilder.
   *
   * @return object - Instanciated React component
   */
  campaignListCreateFn(Component, props) {
    const campaignViewRoute = this.props.sectionConfig.campaignViewRoute;
    const typeUrlParam = 'set';

    if (props.component === 'GridField') {
      const extendedProps = Object.assign({}, props, {
        data: Object.assign({}, props.data, {
          handleDrillDown: (event, record) => {
            // Set url and set list
            const path = campaignViewRoute
              .replace(/:type\?/, typeUrlParam)
              .replace(/:id\?/, record.ID)
              .replace(/:view\?/, 'show');

            window.ss.router.show(path);
          },
          handleEditRecord: (event, id) => {
            const path = campaignViewRoute
              .replace(/:type\?/, typeUrlParam)
              .replace(/:id\?/, id)
              .replace(/:view\?/, 'edit');

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

  /*
   * Hook to allow customisation of components being constructed
   * by the Campaign detail edit FormBuilder.
   *
   * @param object Component - Component constructor.
   * @param object props - Props passed from FormBuilder.
   *
   * @return object - Instanciated React component
   */
  campaignEditCreateFn(Component, props) {
    if (props.name === 'action_save') {
      const extendedProps = Object.assign({}, props, {
        bootstrapButtonStyle: 'success',
        type: 'submit',
        label: props.title,
        icon: 'save',
      });

      return <Component key={props.name} {...extendedProps} />;
    }

    return <Component key={props.name} {...props} />;
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
  actions: React.PropTypes.object.isRequired,
  campaignId: React.PropTypes.string,
  config: React.PropTypes.shape({
    forms: React.PropTypes.shape({
      editForm: React.PropTypes.shape({
        schemaUrl: React.PropTypes.string,
      }),
    }),
    SecurityID: React.PropTypes.string,
  }),
  sectionConfig: React.PropTypes.object.isRequired,
  sectionConfigKey: React.PropTypes.string.isRequired,
  view: React.PropTypes.string,
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
