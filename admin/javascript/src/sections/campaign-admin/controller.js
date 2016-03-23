import React from 'react';
import { connect } from 'react-redux';
import SilverStripeComponent from 'silverstripe-component';
import ActionButton from 'action-button';
import i18n from 'i18n';
import NorthHeader from 'north-header';
import GridField from 'grid-field';
import Config from 'config';

class CampaignAdminContainer extends SilverStripeComponent {

    constructor(props) {
        super(props);

        this.addCampaign = this.addCampaign.bind(this);
    }

    render() {
        const sectionConfig = Config.getSection(this.props.sectionConfigKey);

        return (
            <div>
                <NorthHeader />

                <ActionButton
                    text={i18n._t('Campaigns.ADDCAMPAIGN')} 
                    type={'secondary'} 
                    icon={'plus-circled'} 
                    handleClick={this.addCampaign} />

                <GridField />
            </div>
        );
    }

    addCampaign() {
        //Add campaign
    }

}

CampaignAdminContainer.propTypes = {
    config: React.PropTypes.shape({
        data: React.PropTypes.shape({
            forms: React.PropTypes.shape({
                editForm: React.PropTypes.shape({
                    schemaUrl: React.PropTypes.string
                })
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
