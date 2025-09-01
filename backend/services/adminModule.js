const mongoService = require('./mongoService');

class AdminModule {
  constructor() {
    this.sections = {
      intro: { enabled: true },
      about: { enabled: true },
      menu: { enabled: true },
      services: { enabled: true },
      events: { enabled: true },
      testimonials: { enabled: true },
      contact: { enabled: true }
    };
    
    this.pages = {
      '/': { enabled: true },
      '/menu': { enabled: true },
      '/events': { enabled: true },
      '/fast/:tableId': { enabled: true }
    };
  }

  async getAdminConfig() {
    try {
      const db = mongoService.getDatabase();
      const configs = await db.collection('configs').find({}).toArray();
      
      return {
        sections: this.sections,
        pages: this.pages,
        configs: configs
      };
    } catch (error) {
      console.error('Ошибка получения конфигурации админки:', error);
      return {
        sections: this.sections,
        pages: this.pages,
        configs: []
      };
    }
  }

  async updateSection(key, enabled) {
    try {
      if (this.sections[key]) {
        this.sections[key].enabled = enabled;
        
        // Сохраняем в базу данных
        const db = mongoService.getDatabase();
        await db.collection('sections').updateOne(
          { sectionId: key },
          { $set: { enabled: enabled } },
          { upsert: true }
        );
        
        return true;
      }
      return false;
    } catch (error) {
      console.error('Ошибка обновления секции:', error);
      return false;
    }
  }

  async updatePage(pagePath, enabled) {
    try {
      if (this.pages[pagePath]) {
        this.pages[pagePath].enabled = enabled;
        return true;
      }
      return false;
    } catch (error) {
      console.error('Ошибка обновления страницы:', error);
      return false;
    }
  }

  async getEnabledSections() {
    try {
      const db = mongoService.getDatabase();
      const sections = await db.collection('sections').find({ enabled: true }).toArray();
      
      // Преобразуем в объект для фронтенда
      const sectionsObject = {};
      sections.forEach(section => {
        sectionsObject[section.sectionId] = section.data || {};
      });
      
      return sectionsObject;
    } catch (error) {
      console.error('Ошибка получения секций:', error);
      return {};
    }
  }

  isPageEnabled(pagePath) {
    return this.pages[pagePath]?.enabled || false;
  }
}

module.exports = new AdminModule();
