import apiService from './apiService';
import { API_ENDPOINTS } from '../constants/apiEndpoints';

export const sectionsService = {
  // Получить все секции
  async getAllSections() {
    try {
      return await apiService.get(API_ENDPOINTS.sections);
    } catch (error) {
      console.error('Error fetching sections:', error);
      throw error;
    }
  },

  // Получить конкретную секцию
  async getSection(sectionName) {
    try {
      const sections = await this.getAllSections();
      return sections[sectionName] || null;
    } catch (error) {
      console.error(`Error fetching section ${sectionName}:`, error);
      throw error;
    }
  },

  // Обновить секцию (админ)
  async updateSection(sectionName, data) {
    try {
      return await apiService.put(`${API_ENDPOINTS.admin.sections}/${sectionName}`, data);
    } catch (error) {
      console.error(`Error updating section ${sectionName}:`, error);
      throw error;
    }
  },

  // Загрузить изображение (админ)
  async uploadImage(file) {
    try {
      const formData = new FormData();
      formData.append('image', file);
      
      return await apiService.post(API_ENDPOINTS.admin.upload, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    } catch (error) {
      console.error('Error uploading image:', error);
      throw error;
    }
  },
};

export default sectionsService;
