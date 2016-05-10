import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import backend from 'lib/Backend';
import * as campaignActions from 'state/campaign/CampaignActions';
import * as breadcrumbsActions from 'state/breadcrumbs/BreadcrumbsActions';
import BreadcrumbComponent from 'components/Breadcrumb/Breadcrumb';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormAction from 'components/FormAction/FormAction';
import i18n from 'i18n';
import Toolbar from 'components/Toolbar/Toolbar';
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
    this.campaignAddCreateFn = this.campaignAddCreateFn.bind(this);
    this.campaignEditCreateFn = this.campaignEditCreateFn.bind(this);
    this.handleBackButtonClick = this.handleBackButtonClick.bind(this);
    this.baseBreadcrumbs = this.baseBreadcrumbs.bind(this);
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
        this.props.campaignActions.showCampaignView(ctx.params.id, ctx.params.view);

        // reset admin to top level of breadcrumbs
        this.setupBreadcrumbs(ctx.params.id, ctx.params.view);
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

  baseBreadcrumbs() {
    return [{
      text: i18n._t('Campaigns.CAMPAIGN', 'Campaigns'),
      href: this.props.sectionConfig.route,
    }];
  }

  setupBreadcrumbs(id, view) {
    // Set root breadcrumb
    const breadcrumbs = this.baseBreadcrumbs();
    switch (view) {
      case 'show':
        // NOOP - Lazy loaded in CampaignAdminList.js
        break;
      case 'edit':
        // @todo - Lazy load in FormBuilder / GridField
        breadcrumbs.push({
          text: i18n._t('Campaigns.EDIT_CAMPAIGN', 'Editing Campaign'),
          href: this.getActionRoute(id, view),
        });
        break;
      case 'create':
        breadcrumbs.push({
          text: i18n._t('Campaigns.ADD_CAMPAIGN', 'Add Campaign'),
          href: this.getActionRoute(id, view),
        });
        break;
      default:
        // NOOP
        break;
    }

    this.props.breadcrumbsActions.setBreadcrumbs(breadcrumbs);
  }

  handleBackButtonClick(event) {
    // Go back to second from last breadcrumb (where last item is current)
    if (this.props.breadcrumbs.length > 1) {
      const last = this.props.breadcrumbs[this.props.breadcrumbs.length - 2];
      if (last && last.href) {
        event.preventDefault();
        window.ss.router.show(last.href);
        return;
      }
    }
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
      case 'create':
        view = this.renderCreateView();
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
    const schemaUrl = this.props.sectionConfig.form.EditForm.schemaUrl;
    const formActionProps = {
      label: i18n._t('Campaigns.ADDCAMPAIGN'),
      icon: 'plus',
      handleClick: this.addCampaign,
    };
    const formBuilderProps = {
      createFn: this.campaignListCreateFn,
      schemaUrl,
    };

    return (
      <div className="cms-content__inner no-preview">
        <div className="cms-content__left cms-campaigns collapse in" aria-expanded="true">
          <Toolbar>
            <BreadcrumbComponent multiline crumbs={this.props.breadcrumbs} />
          </Toolbar>
          <div className="panel-scrollable--single-toolbar">
            <div className="toolbar--content">
              <div className="btn-toolbar">
                <FormAction {...formActionProps} />
              </div>
            </div>
            <div className="container-fluid">
              <FormBuilder {...formBuilderProps} />
            </div>
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
      sectionConfig: this.props.sectionConfig,
      campaignId: this.props.campaignId,
      itemListViewEndpoint: this.props.sectionConfig.itemListViewEndpoint,
      publishApi: this.publishApi,
      baseBreadcrumbs: this.baseBreadcrumbs(),
      handleBackButtonClick: this.handleBackButtonClick,
    };

    return (
      <CampaignAdminList {...props} />
    );
  }

  /**
   * Renders the Detail Edit Form for a Campaign.
   */
  renderDetailEditView() {
    const baseSchemaUrl = this.props.sectionConfig.form.DetailEditForm.schemaUrl;
    const formBuilderProps = {
      createFn: this.campaignEditCreateFn,
      schemaUrl: `${baseSchemaUrl}/ChangeSet/${this.props.campaignId}`,
    };

    return (
      <div className="cms-content__inner">
        <Toolbar showBackButton handleBackButtonClick={this.handleBackButtonClick}>
          <BreadcrumbComponent multiline crumbs={this.props.breadcrumbs} />
        </Toolbar>
        <div className="panel-scrollable--single-toolbar container-fluid spacer--top-md">
          <FormBuilder {...formBuilderProps} />
        </div>
      </div>
    );
  }

  /**
   * Render the view for creating a new Campaign.
   */
  renderCreateView() {
    const baseSchemaUrl = this.props.sectionConfig.form.DetailEditForm.schemaUrl;
    const formBuilderProps = {
      createFn: this.campaignAddCreateFn,
      schemaUrl: `${baseSchemaUrl}/ChangeSet`,
    };

    return (
      <div className="cms-content__inner">
        <Toolbar showBackButton handleBackButtonClick={this.handleBackButtonClick}>
          <BreadcrumbComponent multiline crumbs={this.props.breadcrumbs} />
        </Toolbar>
        <div className="panel-scrollable--single-toolbar container-fluid spacer--top-md">
          <FormBuilder {...formBuilderProps} />
        </div>
      </div>
    );
  }

  /**
   * Hook to allow customisation of components being constructed
   * by the Campaign DetailEdit FormBuilder.
   *
   * @param {Object} Component - Component constructor.
   * @param {Object} props - Props passed from FormBuilder.
   *
   * @return {Object} - Instanciated React component
   */
  campaignEditCreateFn(Component, props) {
    const indexRoute = this.props.sectionConfig.route;

    // Route to the Campaigns index view when 'Cancel' is clicked.
    if (props.name === 'action_cancel') {
      const extendedProps = Object.assign({}, props, {
        handleClick: (event) => {
          event.preventDefault();
          window.ss.router.show(indexRoute);
        },
      });

      return <Component key={props.name} {...extendedProps} />;
    }

    return <Component key={props.name} {...props} />;
  }

  /**
   * Hook to allow customisation of components being constructed
   * by the Campaign creation FormBuilder.
   *
   * @param {Object} Component - Component constructor.
   * @param {Object} props - Props passed from FormBuilder.
   *
   * @return {Object} - Instanciated React component
   */
  campaignAddCreateFn(Component, props) {
    const indexRoute = this.props.sectionConfig.route;

    // Route to the Campaigns index view when 'Cancel' is clicked.
    if (props.name === 'action_cancel') {
      const extendedProps = Object.assign({}, props, {
        handleClick: (event) => {
          event.preventDefault();
          window.ss.router.show(indexRoute);
        },
      });

      return <Component key={props.name} {...extendedProps} />;
    }

    return <Component key={props.name} {...props} />;
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

  addCampaign() {
    const path = this.getActionRoute(0, 'create');
    window.ss.router.show(path);
  }

  /**
   * Generate route with the given id and view
   * @param {numeric} id
   * @param {string} view
   */
  getActionRoute(id, view) {
    return this.props.sectionConfig.campaignViewRoute
      .replace(/:type\?/, 'set')
      .replace(/:id\?/, id)
      .replace(/:view\?/, view);
  }
}

CampaignAdmin.propTypes = {
  breadcrumbsActions: React.PropTypes.object.isRequired,
  campaignActions: React.PropTypes.object.isRequired,
  campaignId: React.PropTypes.string,
  config: React.PropTypes.shape({
    form: React.PropTypes.shape({
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
    breadcrumbs: state.breadcrumbs,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    breadcrumbsActions: bindActionCreators(breadcrumbsActions, dispatch),
    campaignActions: bindActionCreators(campaignActions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(CampaignAdmin);
