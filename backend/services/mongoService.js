const { MongoClient } = require('mongodb');

class MongoService {
  constructor() {
    this.client = null;
    this.db = null;
    this.isConnected = false;
  }

  async connect() {
    try {
      const mongoUrl = process.env.MONGODB_URL || 'mongodb://127.0.0.1:27017';
      const dbName = process.env.MONGODB_DB || 'goodzone';
      
      console.log(`🔗 Подключение к MongoDB: ${mongoUrl}/${dbName}`);
      
      this.client = new MongoClient(mongoUrl);
      await this.client.connect();
      this.db = this.client.db(dbName);
      this.isConnected = true;
      
      console.log('✅ MongoDB подключена успешно');
      
      // Создаем индексы
      await this.createIndexes();
      
      return this.db;
    } catch (error) {
      console.error('❌ Ошибка подключения к MongoDB:', error);
      throw error;
    }
  }

  async createIndexes() {
    try {
      // Индекс для переводов по языку
      await this.db.collection('translations').createIndex({ language: 1 }, { unique: true });
      
      // Индекс для конфигов по типу
      await this.db.collection('configs').createIndex({ type: 1 }, { unique: true });
      
      console.log('✅ Индексы MongoDB созданы');
    } catch (error) {
      console.error('⚠️ Ошибка создания индексов:', error.message);
    }
  }

  getDatabase() {
    if (!this.isConnected || !this.db) {
      throw new Error('MongoDB не подключена. Вызовите connect() сначала.');
    }
    return this.db;
  }

  async disconnect() {
    if (this.client) {
      await this.client.close();
      this.isConnected = false;
      console.log('✅ MongoDB отключена');
    }
  }

  // === МЕТОДЫ ДЛЯ ПЕРЕВОДОВ ===
  
  async getTranslations(language) {
    const db = this.getDatabase();
    const translation = await db.collection('translations').findOne({ language });
    return translation ? translation.data : null;
  }

  async setTranslations(language, data) {
    const db = this.getDatabase();
    const result = await db.collection('translations').replaceOne(
      { language },
      {
        language,
        data,
        updatedAt: new Date(),
        version: 1
      },
      { upsert: true }
    );
    return result;
  }

  async getAllTranslations() {
    const db = this.getDatabase();
    const translations = await db.collection('translations').find({}).toArray();
    const result = {};
    translations.forEach(t => {
      result[t.language] = t.data;
    });
    return result;
  }

  // === МЕТОДЫ ДЛЯ КОНФИГУРАЦИЙ ===
  
  async getConfig(type) {
    const db = this.getDatabase();
    const config = await db.collection('configs').findOne({ type });
    return config ? config.data : null;
  }

  async setConfig(type, data) {
    const db = this.getDatabase();
    const result = await db.collection('configs').replaceOne(
      { type },
      {
        type,
        data,
        updatedAt: new Date(),
        version: 1
      },
      { upsert: true }
    );
    return result;
  }

  async getAllConfigs() {
    const db = this.getDatabase();
    const configs = await db.collection('configs').find({}).toArray();
    const result = {};
    configs.forEach(c => {
      result[c.type] = c.data;
    });
    return result;
  }
}

// Экспортируем единственный экземпляр
const mongoService = new MongoService();
module.exports = mongoService;
