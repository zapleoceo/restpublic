import { SectionWrapper } from './SectionWrapper';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const IntroSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const introContent = content.intro || {
    title: t('intro.title'),
    subtitle: t('intro.subtitle'),
    background_image: '/template/images/intro-pic-primary.jpg'
  };
  
  return (
    <SectionWrapper id="intro" className="s-intro">
      <div className="grid-block s-intro__content">
        <div className="intro-header">
          <div className="intro-header__overline">
            {t('intro.welcome')}
          </div>
          <h1 className="intro-header__big-type">
            {introContent.title}
          </h1>
        </div>
        
        <figure className="intro-pic-primary">
          <img 
            src={introContent.background_image} 
            alt="North Republic"
            srcSet={`${introContent.background_image} 1x, ${introContent.background_image.replace('.jpg', '@2x.jpg')} 2x`}
          />
        </figure>
        
        <div className="intro-block-content">
          <figure className="intro-block-content__pic">
            <img 
              src="/template/images/intro-pic-secondary.jpg" 
              alt="North Republic"
              srcSet="/template/images/intro-pic-secondary.jpg 1x, /template/images/intro-pic-secondary@2x.jpg 2x"
            />
          </figure>
          
          <div className="intro-block-content__text-wrap">
            <p className="intro-block-content__text">
              {introContent.subtitle}
            </p>
            
            <ul className="intro-block-content__social">
              <li><a href="#0">FB</a></li>
              <li><a href="#0">IG</a></li>
              <li><a href="#0">PI</a></li>
              <li><a href="#0">X</a></li>
            </ul>
          </div>
        </div>

        <div className="intro-scroll">
          <button 
            className="smoothscroll"
            onClick={() => {
              const aboutSection = document.querySelector('#about');
              if (aboutSection) {
                aboutSection.scrollIntoView({ behavior: 'smooth' });
              }
            }}
          >
            <span className="intro-scroll__circle-text"></span>
            <span className="intro-scroll__text u-screen-reader-text">Scroll Down</span>
            <div className="intro-scroll__icon">
              <svg 
                clipRule="evenodd" 
                fillRule="evenodd" 
                strokeLinejoin="round" 
                strokeMiterlimit="2" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path d="m5.214 14.522s4.505 4.502 6.259 6.255c.146.147.338.22.53.22s.384-.073.53-.22c1.754-1.752 6.249-6.244 6.249-6.244.144-.144.216-.334.217-.523 0-.193-.074-.386-.221-.534-.293-.293-.766-.294-1.057-.004l-4.968 4.968v-14.692c0-.414-.336-.75-.75-.75s-.75.336-.75.75v14.692l-4.979-4.978c-.289-.289-.761-.287-1.054.006-.148.148-.222.341-.221.534 0 .189.071.377.215.52z" fillRule="nonzero"/>
              </svg>
            </div>
          </button>
        </div>
      </div>
    </SectionWrapper>
  );
};
