import React from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const AboutSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const aboutContent = content?.about || {
    title: "О нас",
    description: "Республика Север - это современный развлекательный комплекс, где вы можете насладиться отличной кухней, активными играми и приятным отдыхом.",
    image: "/img/about-main.jpg"
  };
  
  return (
    <section id="about" className="container s-about target-section">
      <div className="row s-about__content">
        <div className="column xl-4 lg-5 md-12 s-about__content-start">
          <div className="section-header" data-num="01">
            <h2 className="text-display-title">{aboutContent.title}</h2>
          </div>
          <figure className="about-pic-primary">
            <img src={aboutContent.image} alt="About" />
          </figure>
        </div>
        
        <div className="column xl-6 lg-6 md-12 s-about__content-end">
          <div className="about-content">
            <p>{aboutContent.description}</p>
          </div>
        </div>
      </div>
    </section>
  );
};
