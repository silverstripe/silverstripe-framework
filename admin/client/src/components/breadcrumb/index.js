import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class BreadcrumbComponent extends SilverStripeComponent {

  render() {
    return (
      <ol className="breadcrumb">
        {this.getBreadcrumbs()}
      </ol>
    );
  }

  getBreadcrumbs() {
    if (typeof this.props.crumbs === 'undefined') {
      return null;
    }

    return [].concat(
     this.props.crumbs.slice(0, -1).map((crumb, index) => [
       <li className="breadcrumb__item">
         <a key={index} className="breadcrumb__item-title" href={crumb.href}>{crumb.text}</a>
        </li>,
     ]),
       this.props.crumbs.slice(-1).map((crumb, index) => [
         <li className="breadcrumb__item breadcrumb__item--last">
           <h2 className="breadcrumb__item-title breadcrumb__item-title--last text-truncate" key={index}>
             {crumb.text}
           </h2>
         </li>,
       ])
    );
  }

}

BreadcrumbComponent.propTypes = {
  crumbs: React.PropTypes.array,
};

export default BreadcrumbComponent;
