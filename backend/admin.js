const fs = require('fs');
const path = require('path');

// Путь к файлу с настройками админки
const ADMIN_CONFIG_PATH = path.join(__dirname, 'admin-config.json');

// Структура настроек по умолчанию
const DEFAULT_ADMIN_CONFIG = {
  sections: {
    menu: {
      enabled: true,
      title: "Меню",
      description: "Ресторанное меню"
    },
    lasertag: {
      enabled: true,
      title: "Лазертаг",
      description: "Командная игра"
    },
    bow: {
      enabled: true,
      title: "Archery Tag",
      description: "Лучный бой"
    },
    cinema: {
      enabled: true,
      title: "Кинотеатр",
      description: "Просмотр фильмов"
    },
    rent: {
      enabled: true,
      title: "BBQ Picnic Area",
      description: "Зона барбекю"
    },
    quests: {
      enabled: true,
      title: "Квесты",
      description: "Увлекательные квесты"
    },
    guitar: {
      enabled: true,
      title: "Гитарники",
      description: "Душевный гитарник у костра"
    },
    boardgames: {
      enabled: true,
      title: "Настольные игры",
      description: "Найди свою компанию"
    },
    yoga: {
      enabled: true,
      title: "Йога",
      description: "Гармония тела и духа"
    }
  },
  pages: {
    "/": { enabled: true, title: "Главная страница" },
    "/m": { enabled: true, title: "Меню" },
    "/lasertag": { enabled: true, title: "Лазертаг" },
    "/bow": { enabled: true, title: "Archery Tag" },
    "/cinema": { enabled: true, title: "Кинотеатр" },
    "/rent": { enabled: true, title: "BBQ Picnic Area" },
    "/quests": { enabled: true, title: "Квесты" },
    "/guitar": { enabled: true, title: "Гитарники" },
    "/boardgames": { enabled: true, title: "Настольные игры" },
    "/yoga": { enabled: true, title: "Йога" }
  },
  lastUpdated: new Date().toISOString()
};

// Функция для загрузки конфигурации
function loadAdminConfig() {
  try {
    if (fs.existsSync(ADMIN_CONFIG_PATH)) {
      const configData = fs.readFileSync(ADMIN_CONFIG_PATH, 'utf8');
      return JSON.parse(configData);
    } else {
      // Создаем файл с настройками по умолчанию
      saveAdminConfig(DEFAULT_ADMIN_CONFIG);
      return DEFAULT_ADMIN_CONFIG;
    }
  } catch (error) {
    console.error('❌ Ошибка загрузки конфигурации админки:', error.message);
    return DEFAULT_ADMIN_CONFIG;
  }
}

// Функция для сохранения конфигурации
function saveAdminConfig(config) {
  try {
    config.lastUpdated = new Date().toISOString();
    fs.writeFileSync(ADMIN_CONFIG_PATH, JSON.stringify(config, null, 2));
    return true;
  } catch (error) {
    console.error('❌ Ошибка сохранения конфигурации админки:', error.message);
    return false;
  }
}

// Функция для получения конфигурации
function getAdminConfig() {
  return loadAdminConfig();
}

// Функция для обновления секции
function updateSection(sectionKey, enabled) {
  const config = loadAdminConfig();
  if (config.sections[sectionKey]) {
    config.sections[sectionKey].enabled = enabled;
    return saveAdminConfig(config);
  }
  return false;
}

// Функция для обновления страницы
function updatePage(pagePath, enabled) {
  const config = loadAdminConfig();
  if (config.pages[pagePath]) {
    config.pages[pagePath].enabled = enabled;
    return saveAdminConfig(config);
  }
  return false;
}

// Функция для проверки доступности секции
function isSectionEnabled(sectionKey) {
  const config = loadAdminConfig();
  return config.sections[sectionKey]?.enabled !== false;
}

// Функция для проверки доступности страницы
function isPageEnabled(pagePath) {
  const config = loadAdminConfig();
  return config.pages[pagePath]?.enabled !== false;
}

// Функция для получения всех доступных секций
function getEnabledSections() {
  const config = loadAdminConfig();
  const enabledSections = {};
  
  Object.keys(config.sections).forEach(key => {
    if (config.sections[key].enabled) {
      enabledSections[key] = config.sections[key];
    }
  });
  
  return enabledSections;
}

// Функция для получения всех доступных страниц
function getEnabledPages() {
  const config = loadAdminConfig();
  const enabledPages = {};
  
  Object.keys(config.pages).forEach(key => {
    if (config.pages[key].enabled) {
      enabledPages[key] = config.pages[key];
    }
  });
  
  return enabledPages;
}

module.exports = {
  getAdminConfig,
  updateSection,
  updatePage,
  isSectionEnabled,
  isPageEnabled,
  getEnabledSections,
  getEnabledPages,
  loadAdminConfig,
  saveAdminConfig
};
