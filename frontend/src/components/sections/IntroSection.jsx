import { SectionWrapper } from '../ui/SectionWrapper';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const IntroSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const introContent = content?.intro || {
    title: "Республика Север",
    subtitle: "Развлекательный комплекс с рестораном"
  };
  
  return (
    <SectionWrapper id="intro" className="s-intro min-h-screen flex items-center">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
        <div className="intro-header">
          <div className="intro-header__overline text-primary-500 font-medium mb-2">
            {t('intro.welcome') || "Добро пожаловать в"}
          </div>
          <h1 className="intro-header__big-type text-4xl md:text-6xl font-serif font-bold text-primary-900 mb-6">
            {introContent.title}
          </h1>
          <div className="intro-block-content">
            <div className="intro-block-content__text-wrap">
              <p className="intro-block-content__text text-lg text-neutral-700 leading-relaxed">
                {introContent.subtitle}
              </p>
            </div>
          </div>
        </div>
        
        <figure className="intro-pic-primary">
          <img 
            src={introContent.background_image || "/img/hero-bg.jpg"} 
            alt="North Republic" 
            className="w-full h-auto rounded-lg shadow-lg"
          />
        </figure>
      </div>
    </SectionWrapper>
  );
};
