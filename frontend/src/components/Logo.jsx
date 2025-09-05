import React from 'react';
import { Link } from 'react-router-dom';

const Logo = () => {
  return (
    <div className="header-logo">
      <Link className="logo" to="/">
        <img 
          src="/images/logo.png" 
          alt="North Republic" 
        />
      </Link>
    </div>
  );
};

export default Logo;
