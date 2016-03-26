import React from 'react';
import NorthHeaderBreadcrumbsComponent from '../north-header-breadcrumbs';
import SilverStripeComponent from 'silverstripe-component.js';

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
