import { SectionWrapper } from '../ui/SectionWrapper';
import { SectionHeader } from '../ui/SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const AboutSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const aboutContent = content?.about || {
    title: "О нас",
    content: "<p>Мы - развлекательный комплекс, где каждый найдет что-то для себя.</p>"
  };
  
  return (
    <SectionWrapper id="about" className="s-about bg-neutral-50">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div className="s-about__content-start">
          <SectionHeader number="01" title={aboutContent.title} />
          <figure className="about-pic-primary mb-6">
            <img 
              src="/img/about-main.jpg" 
              alt="About" 
              className="w-full h-auto rounded-lg shadow-lg"
            />
          </figure>
        </div>
        
        <div className="s-about__content-end">
          <div 
            className="prose prose-lg max-w-none"
            dangerouslySetInnerHTML={{ __html: aboutContent.content }} 
          />
        </div>
      </div>
    </SectionWrapper>
  );
};
