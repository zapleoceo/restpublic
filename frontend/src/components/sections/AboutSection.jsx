import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const AboutSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const aboutContent = content.about || {
    title: t('section.about.title'),
    description: 'North Republic - это уникальное место, где каждый найдет что-то для себя. Мы предлагаем широкий спектр развлечений и услуг для всех возрастов.',
    features: [
      'Современные развлечения',
      'Профессиональный персонал',
      'Уютная атмосфера',
      'Доступные цены'
    ],
    image: '/template/images/about-pic-primary.jpg'
  };

  return (
    <SectionWrapper id="about" className="bg-gray-50">
      <SectionHeader number="01" title={aboutContent.title} />
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div className="space-y-6">
          <p className="text-lg text-gray-600 leading-relaxed">
            {aboutContent.description}
          </p>
          
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {aboutContent.features.map((feature, index) => (
              <div key={index} className="flex items-center space-x-3">
                <div className="w-2 h-2 bg-primary-600 rounded-full"></div>
                <span className="text-gray-700">{feature}</span>
              </div>
            ))}
          </div>
          
          <button className="bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-700 transition-colors">
            {t('button.learn_more')}
          </button>
        </div>
        
        <div className="relative">
          <img
            src={aboutContent.image}
            alt="О нас"
            className="w-full h-auto rounded-lg shadow-xl"
          />
        </div>
      </div>
    </SectionWrapper>
  );
};
