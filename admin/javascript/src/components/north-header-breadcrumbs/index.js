import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';

class NorthHeaderBreadcrumbsComponent extends SilverStripeComponent {

    render() {
        return (
            <div className="cms-content-header-info">
                <div className="breadcrumbs-wrapper">
                    <h2 id="page-title-heading">
                        {this.getBreadcrumbs()}
                    </h2>
                </div>
            </div>
        );
    }

    getBreadcrumbs() {
        if (typeof this.props.crumbs === 'undefined') {
            return null;
        }

        var breadcrumbs = this.props.crumbs.map((crumb, index, crumbs) => {
            // If its the last item in the array
            if (index === crumbs.length - 1) {
                return <span key={index} className="crumb last">{crumb.text}</span>;
            } else {
                return [
                    <a key={index} className="cms-panel-link crumb" href={crumb.href}>{crumb.text}</a>,
                    <span className="sep">/</span>
                ];
            }
        });

        return breadcrumbs;
    }

}

export default NorthHeaderBreadcrumbsComponent;
