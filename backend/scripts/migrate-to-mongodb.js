#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const mongoService = require('../services/mongoService');

async function migrateTranslations() {
  console.log('📄 Миграция переводов...');
  
  const langDir = path.join(__dirname, '../../frontend/public/lang');
  const languages = ['ru', 'en', 'vi'];
  
  for (const lang of languages) {
    const filePath = path.join(langDir, `${lang}.json`);
    
    if (fs.existsSync(filePath)) {
      try {
        const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));
        await mongoService.setTranslations(lang, data);
        console.log(`✅ ${lang}.json → MongoDB`);
      } catch (error) {
        console.error(`❌ Ошибка миграции ${lang}.json:`, error.message);
      }
    } else {
      console.warn(`⚠️ Файл ${lang}.json не найден`);
    }
  }
}

async function migrateSiteConfig() {
  console.log('⚙️ Миграция конфигурации сайта...');
  
  try {
    // Импортируем конфигурацию сайта
    const siteConfigPath = path.join(__dirname, '../../frontend/src/constants/siteConfig.js');
    
    if (fs.existsSync(siteConfigPath)) {
      // Читаем файл как текст и парсим экспорты
      const configContent = fs.readFileSync(siteConfigPath, 'utf8');
      
      // Простой парсинг экспортов (можно улучшить)
      const siteNameMatch = configContent.match(/export const SITE_NAME = ({[\s\S]*?});/);
      const siteDescMatch = configContent.match(/export const SITE_DESCRIPTION = ({[\s\S]*?});/);
      
      let siteConfig = {};
      
      if (siteNameMatch) {
        siteConfig.SITE_NAME = eval(`(${siteNameMatch[1]})`);
      }
      
      if (siteDescMatch) {
        siteConfig.SITE_DESCRIPTION = eval(`(${siteDescMatch[1]})`);
      }
      
      await mongoService.setConfig('site_config', siteConfig);
      console.log('✅ siteConfig.js → MongoDB');
    }
  } catch (error) {
    console.error('❌ Ошибка миграции конфигурации сайта:', error.message);
  }
}

async function migrateSectionsConfig() {
  console.log('🏗️ Миграция конфигурации секций...');
  
  try {
    // Базовая конфигурация секций (из HomePage.jsx)
    const sectionsConfig = {
      menu: {
        id: 'menu',
        icon: '/img/menu/icon.png',
        logo: '/img/menu/big.jpg',
        link: '/menu',
        enabled: true
      },
      lasertag: {
        id: 'lasertag',
        icon: '/img/lazertag/icon.png',
        logo: '/img/lazertag/logo.png',
        link: '/lasertag',
        enabled: true
      },
      bow: {
        id: 'bow',
        icon: '/img/archery/icon.png',
        logo: '/img/archery/logo.png',
        link: '/archerytag',
        enabled: true
      },
      cinema: {
        id: 'cinema',
        icon: '/img/cinema/icon.png',
        logo: '/img/cinema/big.jpg',
        link: '/cinema',
        enabled: true
      },
      rent: {
        id: 'rent',
        icon: '/img/bbq/icon.png',
        logo: '/img/bbq/buttton.png',
        link: '/bbq_zone',
        enabled: true
      },
      quests: {
        id: 'quests',
        icon: '/img/quests/icon.png',
        logo: '/img/quests/big.jpg',
        link: '/quests',
        enabled: true
      },
      guitar: {
        id: 'guitar',
        icon: '/img/guitar/icon.png',
        logo: '/img/guitar/button.jpg',
        link: '/guitar',
        enabled: true
      },
      boardgames: {
        id: 'boardgames',
        icon: '/img/boardgames/icon.png',
        logo: '/img/boardgames/button.jpg',
        link: '/boardgames',
        enabled: true
      },
      yoga: {
        id: 'yoga',
        icon: '/img/yoga/icon.png',
        logo: '/img/yoga/button.jpg?v=1',
        link: '/yoga',
        enabled: true
      }
    };
    
    await mongoService.setConfig('sections', sectionsConfig);
    console.log('✅ sectionsConfig → MongoDB');
  } catch (error) {
    console.error('❌ Ошибка миграции секций:', error.message);
  }
}

async function main() {
  try {
    console.log('🚀 Начинаем миграцию в MongoDB...\n');
    
    // Подключаемся к MongoDB
    await mongoService.connect();
    
    // Выполняем миграции
    await migrateTranslations();
    console.log('');
    
    await migrateSiteConfig();
    console.log('');
    
    await migrateSectionsConfig();
    console.log('');
    
    console.log('🎉 Миграция завершена успешно!');
    
    // Проверяем результат
    const translations = await mongoService.getAllTranslations();
    const configs = await mongoService.getAllConfigs();
    
    console.log('\n📊 Результат миграции:');
    console.log('- Переводы:', Object.keys(translations).join(', '));
    console.log('- Конфигурации:', Object.keys(configs).join(', '));
    
    // ЯВНО завершаем процесс после успешной миграции
    console.log('\n✅ Миграция завершена, завершаем процесс...');
    process.exit(0);
    
  } catch (error) {
    console.error('💥 Ошибка миграции:', error);
    process.exit(1);
  }
}

// Запуск миграции
if (require.main === module) {
  main();
}

module.exports = { main };
