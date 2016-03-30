import React from 'react';
import NorthHeaderBreadcrumbsComponent from '../north-header-breadcrumbs/index';
import SilverStripeComponent from 'silverstripe-component';

class NorthHeaderComponent extends SilverStripeComponent {

    render() {
        return (
            <div className="north-header-component">
                <NorthHeaderBreadcrumbsComponent crumbs={this.getBreadcrumbs()}/>
            </div>
        );
    }

    getBreadcrumbs() {
        return [
            {
                text: 'Campaigns',
                href: 'admin/campaigns'
            },
            {
                text: 'March release',
                href: 'admin/campaigns/show/1'
            }
        ]
    }

}

export default NorthHeaderComponent;
