import React from 'react';
import PageContainer from './PageContainer';
import HeroSection from './HeroSection';
import MenuSection from './MenuSection';
import ContactSection from './ContactSection';

const HomePage = ({ menuData }) => {
  const categories = menuData?.categories || [];
  const products = menuData?.products || [];

  return (
    <PageContainer>
      {/* Hero Section */}
      <HeroSection />

      {/* Menu Categories */}
      <MenuSection 
        categories={categories} 
        products={products} 
        limit={2} 
      />

      {/* Contact Section */}
      <ContactSection />
    </PageContainer>
  );
};

export default HomePage;
