import React, { StatelessComponent, useState } from "react";
import Header from './Header';
import Sidebar from './Sidebar';
import useWindowWidth from '../hooks/useWindowWidth';

interface LayoutProps {
  children: any[];
}

const LARGE_SCREEN_SIZE = 1200;

const Layout: StatelessComponent<LayoutProps> = ({ children}) => {
  const [isToggled, setSidebarOpen] = useState(false);
  //const isLarge = useWindowWidth() >= LARGE_SCREEN_SIZE;
  //const isOpen = isLarge ? true : isToggled;
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
