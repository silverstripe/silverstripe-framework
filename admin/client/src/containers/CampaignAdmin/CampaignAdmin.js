import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'react-router';
import backend from 'lib/Backend';
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

    this.publishApi = backend.createEndpointFetcher({
      url: this.props.sectionConfig.publishEndpoint.url,
      method: this.props.sectionConfig.publishEndpoint.method,
      defaultData: { SecurityID: this.props.securityId },
      payloadSchema: {
        id: { urlReplacement: ':id', remove: true },
      },
    });
    this.handleBackButtonClick = this.handleBackButtonClick.bind(this);
  }

  componentWillReceiveProps(props) {
    const hasChangedRoute = (
      this.props.params.id !== props.params.id ||
      this.props.params.view !== props.params.view
    );
    if (hasChangedRoute) {
      this.setBreadcrumbs(props.params.view, props.params.id);
    }
  }

  setBreadcrumbs(view, id) {
    // Set root breadcrumb
    const breadcrumbs = [{
      text: i18n._t('Campaigns.CAMPAIGN', 'Campaigns'),
      href: this.props.sectionConfig.url,
    }];
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
        this.props.router.push(last.href);
        return;
      }
    }
  }

  render() {
    let view = null;

    switch (this.props.params.view) {
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
      handleClick: this.addCampaign.bind(this),
    };
    const formBuilderProps = {
      createFn: this.campaignListCreateFn.bind(this),
      schemaUrl,
    };

    return (
      <div className="cms-content__inner no-preview">
        <div className="cms-content__left cms-campaigns collapse in" aria-expanded="true">
          <Toolbar>
            <BreadcrumbComponent multiline crumbs={this.props.breadcrumbs} />
          </Toolbar>
          <div className="panel-scrollable panel-scrollable--single-toolbar">
            <div className="toolbar--content">
              <div className="btn-toolbar">
                <FormAction {...formActionProps} />
              </div>
            </div>
            <div className="campaign-admin container-fluid">
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
      campaignId: this.props.params.id,
      itemListViewEndpoint: this.props.sectionConfig.itemListViewEndpoint,
      publishApi: this.publishApi,
      handleBackButtonClick: this.handleBackButtonClick.bind(this),
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
      createFn: this.campaignEditCreateFn.bind(this),
      schemaUrl: `${baseSchemaUrl}/${this.props.params.id}`,
    };

    return (
      <div className="cms-content__inner">
        <Toolbar showBackButton handleBackButtonClick={this.handleBackButtonClick}>
          <BreadcrumbComponent multiline crumbs={this.props.breadcrumbs} />
        </Toolbar>

        <div className="panel-scrollable panel-scrollable--single-toolbar container-fluid m-t-1">
          <div className="form--inline">
            <FormBuilder {...formBuilderProps} />
          </div>
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
      createFn: this.campaignAddCreateFn.bind(this),
      schemaUrl: `${baseSchemaUrl}/${this.props.params.id}`,
    };

    return (
      <div className="cms-content__inner">
        <Toolbar showBackButton handleBackButtonClick={this.handleBackButtonClick}>
          <BreadcrumbComponent multiline crumbs={this.props.breadcrumbs} />
        </Toolbar>
        <div className="panel-scrollable panel-scrollable--single-toolbar container-fluid m-t-1">
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
    const url = this.props.sectionConfig.url;

    // Route to the Campaigns index view when 'Cancel' is clicked.
    if (props.name === 'action_cancel') {
      const extendedProps = Object.assign({}, props, {
        handleClick: (event) => {
          event.preventDefault();
          this.props.router.push(url);
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
    const url = this.props.sectionConfig.url;

    // Route to the Campaigns index view when 'Cancel' is clicked.
    if (props.name === 'action_cancel') {
      const extendedProps = Object.assign({}, props, {
        handleClick: (event) => {
          event.preventDefault();
          this.props.router.push(url);
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
    const sectionUrl = this.props.sectionConfig.url;
    const typeUrlParam = 'set';

    if (props.component === 'GridField') {
      const extendedProps = Object.assign({}, props, {
        data: Object.assign({}, props.data, {
          handleDrillDown: (event, record) => {
            this.props.router.push(`${sectionUrl}/${typeUrlParam}/${record.ID}/show`);
          },
          handleEditRecord: (event, id) => {
            this.props.router.push(`${sectionUrl}/${typeUrlParam}/${id}/edit`);
          },
        }),
      });

      return <Component key={extendedProps.name} {...extendedProps} />;
    }

    return <Component key={props.name} {...props} />;
  }

  addCampaign() {
    const path = this.getActionRoute(0, 'create');
    this.props.router.push(path);
  }

  /**
   * Generate route with the given id and view
   * @param {numeric} id
   * @param {string} view
   */
  getActionRoute(id, view) {
    return `${this.props.sectionConfig.url}/set/${id}/${view}`;
  }
}

CampaignAdmin.propTypes = {
  breadcrumbsActions: React.PropTypes.object.isRequired,
  campaignId: React.PropTypes.string,
  sectionConfig: React.PropTypes.object.isRequired,
  securityId: React.PropTypes.string.isRequired,
  view: React.PropTypes.string,
};

function mapStateToProps(state) {
  return {
    config: state.config,
    campaignId: state.campaign.campaignId,
    view: state.campaign.view,
    breadcrumbs: state.breadcrumbs,
    sectionConfig: state.config.sections.CampaignAdmin,
    securityId: state.config.SecurityID,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    breadcrumbsActions: bindActionCreators(breadcrumbsActions, dispatch),
  };
}

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(CampaignAdmin));
