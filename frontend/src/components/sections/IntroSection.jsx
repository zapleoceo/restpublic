import React from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const IntroSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const introContent = content?.intro || {
    title: "Республика Север",
    subtitle: "Развлекательный комплекс с рестораном",
    description: "Добро пожаловать в Республику Север - место, где каждый найдет что-то для себя!"
  };
  
  return (
    <section id="intro" className="container s-intro target-section">
      <div className="grid-block s-intro__content">
        <div className="intro-header">
          <div className="intro-header__overline">
            {t('intro.welcome') || "Добро пожаловать в"}
          </div>
          <h1 className="intro-header__big-type">
            {introContent.title}
          </h1>
        </div>
        
        <figure className="intro-pic-primary">
          <img 
            src={introContent.background_image || "/img/hero-bg.jpg"} 
            alt="North Republic" 
          />
        </figure>
        
        <div className="intro-block-content">
          <div className="intro-block-content__text-wrap">
            <p className="intro-block-content__text">
              {introContent.description}
            </p>
          </div>
        </div>
      </div>
    </section>
  );
};
