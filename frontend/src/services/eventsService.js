import { apiService } from './apiService';

export const eventsService = {
  // Получить все события
  getEvents: async () => {
    return await apiService.get('/api/events');
  },

  // Получить событие по ID
  getEvent: async (id) => {
    return await apiService.get(`/api/events/${id}`);
  },

  // Создать новое событие
  createEvent: async (eventData) => {
    return await apiService.post('/api/events', eventData);
  },

  // Обновить событие
  updateEvent: async (id, eventData) => {
    return await apiService.put(`/api/events/${id}`, eventData);
  },

  // Удалить событие
  deleteEvent: async (id) => {
    return await apiService.delete(`/api/events/${id}`);
  },

  // Получить события по категории
  getEventsByCategory: async (category) => {
    return await apiService.get(`/api/events?category=${category}`);
  },

  // Получить события по статусу
  getEventsByStatus: async (status) => {
    return await apiService.get(`/api/events?status=${status}`);
  },

  // Получить события по дате
  getEventsByDate: async (date) => {
    return await apiService.get(`/api/events?date=${date}`);
  }
};
