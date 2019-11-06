import React, { StatelessComponent, useState } from "react";
import Header from './Header';
import Sidebar from './Sidebar';

const Layout: StatelessComponent<{}> = ({ children}) => {
  const [isToggled, setSidebarOpen] = useState(false);
  return (
    <>
    <Header handleSidebarToggle={() => setSidebarOpen(!isToggled)} />
    <div className={`docs-wrapper container ${isToggled ? 'sidebar-visible' : ''}`}>
    <Sidebar isOpen={isToggled} />
    <div className="docs-content">
      <div className="container">
        <article className="docs-article">
          {children}
        </article>
      </div> 
    </div>
  </div>
    </>
  );
};
export default Layout;
