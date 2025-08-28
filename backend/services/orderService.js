const axios = require('axios');

class OrderService {
  constructor() {
    this.baseUrl = 'https://joinposter.com/api/v3';
    this.token = process.env.POSTER_API_TOKEN;
  }

  /**
   * Проверить существующего клиента по номеру телефона
   */
  async checkExistingClient(phone) {
    try {
      const response = await axios.get(`${this.baseUrl}/clients.getClientsList`, {
        params: {
          token: this.token,
          phone: phone
        }
      });

      if (response.data && response.data.response && response.data.response.length > 0) {
        return {
          exists: true,
          client: response.data.response[0]
        };
      }

      return { exists: false, client: null };
    } catch (error) {
      console.error('Error checking existing client:', error);
      throw new Error('Ошибка при проверке существующего клиента');
    }
  }

  /**
   * Создать нового клиента
   */
  async createClient(clientData) {
    try {
      const response = await axios.post(`${this.baseUrl}/clients.createClient`, {
        token: this.token,
        client_name: clientData.name,
        client_lastname: clientData.lastName || '',
        client_phone: clientData.phone,
        client_birthday: clientData.birthday || '',
        client_sex: clientData.gender === 'male' ? 1 : (clientData.gender === 'female' ? 2 : 0)
      });

      if (response.data && response.data.response) {
        return response.data.response;
      }

      throw new Error('Неверный ответ от API при создании клиента');
    } catch (error) {
      console.error('Error creating client:', error);
      throw new Error('Ошибка при создании клиента');
    }
  }

  /**
   * Проверить, является ли заказ первым для клиента
   */
  async checkFirstOrder(clientId) {
    try {
      const response = await axios.get(`${this.baseUrl}/dash.getTransactions`, {
        params: {
          token: this.token,
          client_id: clientId,
          type: 'incoming_order'
        }
      });

      // Если нет транзакций - это первый заказ
      const hasOrders = response.data && response.data.response && response.data.response.length > 0;
      return !hasOrders;
    } catch (error) {
      console.error('Error checking first order:', error);
      // При ошибке считаем, что это не первый заказ (безопаснее)
      return false;
    }
  }

  /**
   * Создать заказ для зарегистрированного клиента
   */
  async createOrder(orderData) {
    try {
      const { items, total, tableId, comment, clientId, isFirstOrder } = orderData;

      // Подготавливаем товары для заказа
      const products = items.map(item => {
        // Получаем числовую цену
        let price = item.price;
        if (typeof price === 'object' && price !== null) {
          // Если цена - объект, берем основную цену
          price = price['1'] || Object.values(price)[0] || 0;
        }
        price = parseFloat(price) || 0;

        return {
          product_id: item.product_id,
          count: item.quantity,
          price: Math.round(price * 100) // Poster API ожидает цену в копейках
        };
      });

      // Подготавливаем данные заказа
      const orderPayload = {
        token: this.token,
        spot_id: tableId || 1, // ID стола/места
        client_id: clientId,
        products: products,
        comment: comment || ''
      };

      // Добавляем скидку 20% если это первый заказ
      if (isFirstOrder) {
        orderPayload.discount = 20; // Процентная скидка
      }

      const response = await axios.post(
        `${this.baseUrl}/incomingOrders.createIncomingOrder`,
        orderPayload
      );

      if (response.data && response.data.response) {
        return response.data.response;
      }

      throw new Error('Неверный ответ от API при создании заказа');
    } catch (error) {
      console.error('Error creating order:', error);
      console.error('Order data:', orderData);
      throw new Error('Ошибка при создании заказа: ' + (error.response?.data?.error || error.message));
    }
  }

  /**
   * Создать заказ для гостя (с минимальными данными клиента)
   */
  async createGuestOrder(orderData) {
    try {
      const { items, total, tableId, comment, customerData } = orderData;

      // Сначала создаем минимального клиента
      const clientData = {
        name: customerData.name,
        phone: customerData.phone
      };

      const client = await this.createClient(clientData);
      
      // Создаем заказ для этого клиента
      const order = await this.createOrder({
        items,
        total,
        tableId,
        comment,
        clientId: client.client_id,
        isFirstOrder: true // Для гостя всегда первый заказ, но без скидки
      });

      return {
        order,
        client
      };
    } catch (error) {
      console.error('Error creating guest order:', error);
      throw new Error('Ошибка при создании заказа для гостя');
    }
  }
}

module.exports = new OrderService();
