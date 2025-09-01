import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const AboutSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const aboutContent = content.about || {
    title: t('about.title'),
    content: `
      <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quasi earum, ut consequuntur pariatur fugiat aliquam voluptatem officia blanditiis ipsa laboriosam ad velit voluptate nisi saepe quisquam minima culpa eaque amet.</p>
      <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Dolorem vero sit neque sequi eius illum at porro aperiam. Iusto reiciendis reprehenderit ipsa molestias sit eaque velit, veritatis quod, cum exercitationem doloribus eos cumque, ipsam voluptate! Nam doloribus quibusdam eos ipsum optio animi ea ex. Atque neque nesciunt numquam fugiat inventore!</p>
    `,
    images: ['/template/images/about-pic-primary.jpg']
  };
  
  return (
    <SectionWrapper id="about" className="s-about">
      <div className="row s-about__content">
        <div className="column xl-4 lg-5 md-12 s-about__content-start">
          <SectionHeader number="01" title={aboutContent.title} />
          
          <figure className="about-pic-primary">
            <img 
              src={aboutContent.images?.[0] || '/template/images/about-pic-primary.jpg'} 
              alt="About North Republic"
              srcSet={`${aboutContent.images?.[0] || '/template/images/about-pic-primary.jpg'} 1x, ${(aboutContent.images?.[0] || '/template/images/about-pic-primary.jpg').replace('.jpg', '@2x.jpg')} 2x`}
            />
          </figure>
        </div>

        <div className="column xl-6 lg-6 md-12 s-about__content-end">
          <div dangerouslySetInnerHTML={{ __html: aboutContent.content }} />
        </div>
      </div>
    </SectionWrapper>
  );
};
