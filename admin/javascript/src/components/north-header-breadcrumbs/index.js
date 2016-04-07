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

    return [].concat(
     this.props.crumbs.slice(0, -1).map((crumb, index) => [
       <a key={index} className="cms-panel-link crumb" href={crumb.href}>{crumb.text}</a>,
       <span className="sep">/</span>,
     ]),
       this.props.crumbs.slice(-1).map((crumb, index) => [
         <span key={index} className="crumb last">{crumb.text}</span>,
       ])
    );
  }

}

export default NorthHeaderBreadcrumbsComponent;
