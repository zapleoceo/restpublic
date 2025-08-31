import React from 'react';
import { Helmet } from 'react-helmet-async';

export const SEOHead = ({ 
  title = "North Republic - Развлекательный комплекс",
  description = "Развлекательный комплекс с рестораном, лазертагом, кинотеатром и многим другим в Хошимине",
  keywords = "ресторан, лазертаг, кинотеатр, развлечения, Хошимин, Вьетнам",
  image = "/img/logo.png",
  url = "https://northrepublic.me",
  type = "website",
  locale = "ru_RU"
}) => {
  const fullTitle = title.includes("North Republic") ? title : `North Republic - ${title}`;
  
  return (
    <Helmet>
      {/* Основные мета-теги */}
      <title>{fullTitle}</title>
      <meta name="description" content={description} />
      <meta name="keywords" content={keywords} />
      <meta name="author" content="North Republic" />
      <meta name="robots" content="index, follow" />
      <link rel="canonical" href={url} />
      
      {/* Open Graph */}
      <meta property="og:title" content={fullTitle} />
      <meta property="og:description" content={description} />
      <meta property="og:image" content={image} />
      <meta property="og:url" content={url} />
      <meta property="og:type" content={type} />
      <meta property="og:locale" content={locale} />
      <meta property="og:site_name" content="North Republic" />
      
      {/* Twitter Card */}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={fullTitle} />
      <meta name="twitter:description" content={description} />
      <meta name="twitter:image" content={image} />
      
      {/* Дополнительные мета-теги */}
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta name="theme-color" content="#468672" />
      <meta name="msapplication-TileColor" content="#468672" />
      
      {/* Favicon */}
      <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png" />
      <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png" />
      <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
      
      {/* Структурированные данные */}
      <script type="application/ld+json">
        {JSON.stringify({
          "@context": "https://schema.org",
          "@type": "Restaurant",
          "name": "North Republic",
          "description": description,
          "url": url,
          "logo": image,
          "image": image,
          "address": {
            "@type": "PostalAddress",
            "addressCountry": "VN",
            "addressLocality": "Ho Chi Minh City",
            "addressRegion": "Ho Chi Minh"
          },
          "telephone": "+84 123 456 789",
          "email": "info@northrepublic.me",
          "servesCuisine": ["Вьетнамская", "Европейская", "Азиатская"],
          "priceRange": "$$",
          "openingHours": [
            "Mo-Su 10:00-23:00"
          ],
          "sameAs": [
            "https://t.me/northrepublic",
            "https://instagram.com/northrepublic"
          ]
        })}
      </script>
    </Helmet>
  );
};
