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
    <div className="home-page">
      <SEOHead 
        title="Главная"
        description="Добро пожаловать в North Republic - развлекательный комплекс с рестораном, лазертагом, кинотеатром и многим другим в Хошимине"
        keywords="ресторан, лазертаг, кинотеатр, развлечения, Хошимин, Вьетнам, North Republic"
      />
      
      <Header />
      
      <main className="main-content pt-16">
        <div className="sections-container">
          <IntroSection />
          <AboutSection />
          <MenuPreviewSection />
          <ServicesSection />
          <EventsSection />
          <TestimonialsSection />
        </div>
      </main>
      
      <Footer />
    </div>
  );
};

export default HomePage;
