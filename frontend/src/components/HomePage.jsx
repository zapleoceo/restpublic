import React from 'react';
import { IntroSection, AboutSection } from './sections';

const HomePage = () => {
  return (
    <div className="s-pagewrap ss-home">
      <IntroSection />
      <AboutSection />
    </div>
  );
};

export default HomePage;
