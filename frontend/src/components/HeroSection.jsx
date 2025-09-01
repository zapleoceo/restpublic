import React from 'react';
import { useTranslation } from 'react-i18next';
import Section from './Section';

const HeroSection = ({ 
  title, 
  subtitle, 
  highlightText = 'RestPublic',
  className = '' 
}) => {
  const { t } = useTranslation();

  const displayTitle = title || t('welcome.title');
  const displaySubtitle = subtitle || t('welcome.subtitle');

  return (
    <Section className={`text-center ${className}`}>
      <h1 className="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
        {displayTitle}{' '}
        <span className="text-orange-500">
          {highlightText}
        </span>
      </h1>
      <p className="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
        {displaySubtitle}
      </p>
    </Section>
  );
};

export default HeroSection;
