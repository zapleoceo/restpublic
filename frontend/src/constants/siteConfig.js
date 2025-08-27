export const SITE_NAME = {
  ru: 'Республика Север',
  en: 'North Republic',
  vi: 'Cộng hòa Bắc'
};

export const SITE_DESCRIPTION = {
  ru: 'Развлекательный комплекс',
  en: 'Entertainment Complex',
  vi: 'Khu phức hợp giải trí'
};

export const getSiteName = (language = 'ru') => {
  return SITE_NAME[language] || SITE_NAME.ru;
};

export const getSiteDescription = (language = 'ru') => {
  return SITE_DESCRIPTION[language] || SITE_DESCRIPTION.ru;
};

