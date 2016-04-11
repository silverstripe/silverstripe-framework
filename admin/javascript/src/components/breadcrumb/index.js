import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class BreadcrumbComponent extends SilverStripeComponent {

  render() {
    const classNames = ['breadcrumb'];
    if (this.props.multiline) {
      classNames.push('breadcrumb--multiline');
    }
    const classNamesStr = classNames.join(' ');

    return (
      <ol className={classNamesStr}>
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
       <li><a key={index} className="" href={crumb.href}>{crumb.text}</a></li>,
     ]),
       this.props.crumbs.slice(-1).map((crumb, index) => [
         <li className="active">
           <h2 className="breadcrumb__crumb--last" key={index}>{crumb.text}</h2>
         </li>,
       ])
    );
  }

}

export default BreadcrumbComponent;
