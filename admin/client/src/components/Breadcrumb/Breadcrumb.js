import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { connect } from 'react-redux';
import { Link } from 'react-router';

class Breadcrumb extends SilverStripeComponent {

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
         <Link
           key={index}
           className="breadcrumb__item-title"
           to={crumb.href}
           onClick={crumb.onClick}
         >{crumb.text}</Link>
        </li>,
     ]),
       this.props.crumbs.slice(-1).map((crumb, index) => {
         const iconClassNames = ['breadcrumb__icon', crumb.icon ? crumb.icon.className : '']
           .join(' ');

         return [
           <li className="breadcrumb__item breadcrumb__item--last">
             <h2 className="breadcrumb__item-title breadcrumb__item-title--last" key={index}>
               {crumb.text}
               {crumb.icon &&
               <span className={iconClassNames} onClick={crumb.icon.action} />
               }
             </h2>
           </li>,
         ];
       })
    );
  }

}

Breadcrumb.propTypes = {
  crumbs: React.PropTypes.array,
};

function mapStateToProps(state) {
  return {
    crumbs: state.breadcrumbs,
  };
}

export { Breadcrumb };

export default connect(mapStateToProps)(Breadcrumb);
