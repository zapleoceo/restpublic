import React from 'react';
import { useTranslation } from 'react-i18next';
import { MapPin, Phone, Send } from 'lucide-react';
import Section from './Section';
import Grid from './Grid';

const ContactSection = ({ className = '' }) => {
  const { t } = useTranslation();

  const contacts = [
    {
      icon: MapPin,
      title: t('contacts.location'),
      value: t('contacts.view_on_map'),
      href: 'https://maps.app.goo.gl/Hgbn5n83PA11NcqLA',
      external: true
    },
    {
      icon: Phone,
      title: t('contacts.phone'),
      value: '+84 349 338 758',
      href: 'tel:+84349338758'
    },
    {
      icon: Send,
      title: t('contacts.group'),
      value: '@gamezone_vietnam',
      href: 'https://t.me/gamezone_vietnam',
      external: true
    }
  ];

  return (
    <Section className={`bg-white ${className}`}>
      <h2 className="text-3xl font-bold text-gray-900 mb-8 text-center">
        {t('contacts.title')}
      </h2>
      <Grid cols={1} mdCols={3} gap={8}>
        {contacts.map((contact, index) => {
          const IconComponent = contact.icon;
          return (
            <a 
              key={index}
              href={contact.href}
              target={contact.external ? '_blank' : undefined}
              rel={contact.external ? 'noopener noreferrer' : undefined}
              className="text-center group hover:scale-105 transition-transform"
            >
              <div className="w-16 h-16 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-200 transition-colors">
                <IconComponent className="w-8 h-8 text-orange-600" />
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">{contact.title}</h3>
              <span className="text-gray-600 group-hover:text-orange-600 transition-colors">
                {contact.value}
              </span>
            </a>
          );
        })}
      </Grid>
    </Section>
  );
};

export default ContactSection;
