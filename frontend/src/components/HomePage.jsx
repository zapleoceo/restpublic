import React from 'react';
import { 
  IntroSection, 
  AboutSection, 
  MenuPreviewSection, 
  ServicesSection, 
  EventsSection, 
  TestimonialsSection 
} from './sections';

const HomePage = () => {
  return (
    <div className="s-pagewrap ss-home">
      <IntroSection />
      <AboutSection />
      <MenuPreviewSection />
      <ServicesSection />
      <EventsSection />
      <TestimonialsSection />
    </div>
  );
};

export default HomePage;
