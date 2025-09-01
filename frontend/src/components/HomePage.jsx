import React from 'react';
import { Header, Footer } from './layout';
import { SEOHead } from './seo/SEOHead';
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
      <SEOHead 
        title="Главная"
        description="Добро пожаловать в North Republic - развлекательный комплекс с рестораном, лазертагом, кинотеатром и многим другим в Хошимине"
        keywords="ресторан, лазертаг, кинотеатр, развлечения, Хошимин, Вьетнам, North Republic"
      />
      
      <Header />
      
      <main className="s-content">
        <IntroSection />
        <AboutSection />
        <MenuPreviewSection />
        <ServicesSection />
        <EventsSection />
        <TestimonialsSection />
      </main>
      
      <Footer />
    </div>
  );
};

export default HomePage;
