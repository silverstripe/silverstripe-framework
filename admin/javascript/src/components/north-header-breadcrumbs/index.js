import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

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

    const breadcrumbs = this.props.crumbs.map((crumb, index, crumbs) => {
      let component;
      // If its the last item in the array
      if (index === crumbs.length - 1) {
        component = <span key={index} className="crumb last">{crumb.text}</span>;
      } else {
        component = [
          <a key={index} className="cms-panel-link crumb" href={crumb.href}>{crumb.text}</a>,
          <span className="sep">/</span>,
        ];
      }
      return component;
    });

    return breadcrumbs;
  }

}

export default NorthHeaderBreadcrumbsComponent;
