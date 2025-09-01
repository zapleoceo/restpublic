import React from 'react';
import { Utensils } from 'lucide-react';

const Logo = () => {
  return (
    <div className="header-logo">
      <a className="logo" href="index.html">
        <div className="logo-container">
          <div className="logo-background">
            <Utensils className="logo-icon" />
          </div>
          <span className="logo-text">North Republic</span>
        </div>
      </a>
    </div>
  );
};

export default Logo;
