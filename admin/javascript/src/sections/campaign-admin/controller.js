import React from 'react';
import SilverStripeComponent from 'silverstripe-component';
import NorthHeader from '../../components/north-header';
import GridField from '../grid-field';

class CampaignAdminContainer extends SilverStripeComponent {

    render() {
        return (
            <div>
                <NorthHeader></NorthHeader>
                <GridField></GridField>
            </div>
        );
    }

}

export default CampaignAdminContainer;
