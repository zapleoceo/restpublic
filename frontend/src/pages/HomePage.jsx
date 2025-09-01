import { IntroSection } from '../components/sections/IntroSection';
import { AboutSection } from '../components/sections/AboutSection';
import { ServicesSection } from '../components/sections/ServicesSection';
import { EventsSection } from '../components/sections/EventsSection';
import { TestimonialsSection } from '../components/sections/TestimonialsSection';

export const HomePage = () => {
  return (
    <main>
      <IntroSection />
      <AboutSection />
      <ServicesSection />
      <EventsSection />
      <TestimonialsSection />
    </main>
  );
};
