import apiService from './apiService';
import { API_ENDPOINTS } from '../constants/apiEndpoints';

export const sectionsService = {
  async getAllSections() {
    const response = await apiService.get(API_ENDPOINTS.sections);
    return response.data;
  },

  async getSection(sectionName) {
    const response = await apiService.get(`${API_ENDPOINTS.sections}/${sectionName}`);
    return response.data;
  },

  async updateSection(sectionName, data) {
    const response = await apiService.put(`${API_ENDPOINTS.sections}/${sectionName}`, data);
    return response.data;
  },

  async uploadImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    
    const response = await apiService.post('/api/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
};

export default sectionsService;
