import React from 'react';
import { connect } from 'react-redux';
import SilverStripeComponent from 'silverstripe-component';
import FormAction from 'components/form-action/index';
import i18n from 'i18n';
import NorthHeader from 'components/north-header/index';
import FormBuilder from 'components/form-builder/index';

class CampaignAdminContainer extends SilverStripeComponent {

    constructor(props) {
        super(props);

        this.addCampaign = this.addCampaign.bind(this);
    }

    render() {
        const schemaUrl = this.props.config.forms.editForm.schemaUrl;

        return (
            <div>
                <NorthHeader />
                <FormAction
                    label={i18n._t('Campaigns.ADDCAMPAIGN')}
                    icon={'plus-circled'}
                    handleClick={this.addCampaign} />
                <FormBuilder schemaUrl={schemaUrl} />
            </div>
        );
    }

    addCampaign() {
        //Add campaign
    }

}

CampaignAdminContainer.propTypes = {
    config: React.PropTypes.shape({
        forms: React.PropTypes.shape({
            editForm: React.PropTypes.shape({
                schemaUrl: React.PropTypes.string
            })
        })
    }),
    sectionConfigKey: React.PropTypes.string.isRequired
};

function mapStateToProps(state, ownProps) {
    return {
        config: state.config.sections[ownProps.sectionConfigKey]
    }
}

export default connect(mapStateToProps)(CampaignAdminContainer);
