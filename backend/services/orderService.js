const axios = require('axios');

class OrderService {
  constructor() {
    this.baseUrl = 'https://joinposter.com/api';
  }

  getToken() {
    const token = process.env.POSTER_API_TOKEN;
    if (!token) {
      throw new Error('POSTER_API_TOKEN не настроен');
    }
    return token;
  }

  /**
   * Проверить существующего клиента по номеру телефона
   */
  async checkExistingClient(phone) {
    try {
      console.log('Checking existing client with phone:', phone);
      
      // Форматируем номер телефона для поиска
      let formattedPhone = phone;
      if (formattedPhone.startsWith('+')) {
        formattedPhone = formattedPhone.substring(1);
      }
      formattedPhone = formattedPhone.replace(/\D/g, '');
      
      console.log('Searching for formatted phone:', formattedPhone);
      
      const response = await axios.get(`${this.baseUrl}/clients.getClients?token=${this.getToken()}`);

      console.log('Client check response:', response.data);
      
      if (response.data && response.data.response) {
        // Фильтруем клиентов по номеру телефона
        const client = response.data.response.find(c => {
          const clientPhone = c.phone || c.phone_number || '';
          const cleanClientPhone = clientPhone.replace(/\D/g, '');
          return cleanClientPhone === formattedPhone;
        });
        
        if (client) {
          return {
            exists: true,
            client: client
          };
        }
      }

      return { exists: false, client: null };
    } catch (error) {
      console.error('Error checking existing client:', error.response?.data || error.message);
      throw new Error('Ошибка при проверке существующего клиента');
    }
  }

  /**
   * Создать нового клиента
   */
  async createClient(clientData) {
    try {
      console.log('Creating client with data:', clientData);
      
      // Форматируем номер телефона для Poster API
      let phone = clientData.phone;
      if (phone.startsWith('+')) {
        phone = phone.substring(1); // Убираем +
      }
      // Убираем все нецифровые символы
      phone = phone.replace(/\D/g, '');
      
      console.log('Formatted phone:', phone);
      
      // Создаем URLSearchParams для form data
      const formData = new URLSearchParams();
      formData.append('client_name', clientData.name);
      formData.append('client_lastname', clientData.lastName || '');
      formData.append('client_phone', phone);
      formData.append('client_birthday', clientData.birthday || '');
      formData.append('client_sex', clientData.gender === 'male' ? 1 : (clientData.gender === 'female' ? 2 : 0));
      formData.append('client_groups_id_client', 1); // Обязательное поле - группа клиентов (New customers)
      
      const response = await axios.post(`${this.baseUrl}/clients.createClient?token=${this.getToken()}`, formData, {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      });

      console.log('Client creation response:', response.data);

      if (response.data && response.data.response) {
        // Poster API возвращает только ID клиента, создаем объект
        return {
          client_id: response.data.response
        };
      }

      throw new Error('Неверный ответ от API при создании клиента');
    } catch (error) {
      console.error('Error creating client:', error.response?.data || error.message);
      throw new Error('Ошибка при создании клиента');
    }
  }

  /**
   * Проверить, является ли заказ первым для клиента
   */
  async checkFirstOrder(clientId) {
    try {
      console.log('Checking first order for client:', clientId);
      const dateFrom = '2020-01-01';
      const dateTo = new Date().toISOString().split('T')[0];
      
      const response = await axios.get(
        `${this.baseUrl}/dash.getTransactions?token=${this.getToken()}&dateFrom=${dateFrom}&dateTo=${dateTo}`
      );

      console.log('First order check response:', response.data);

      // Фильтруем транзакции по client_id
      if (response.data && response.data.response && response.data.response.data) {
        const clientTransactions = response.data.response.data.filter(t => 
          t.client_id === clientId.toString()
        );
        
        // Если нет транзакций для этого клиента - это первый заказ
        return clientTransactions.length === 0;
      }
      
      return true; // Если нет данных, считаем первым заказом
    } catch (error) {
      console.error('Error checking first order:', error.response?.data || error.message);
      // При ошибке считаем, что это не первый заказ (безопаснее)
      return false;
    }
  }

  /**
   * Создать заказ для зарегистрированного клиента
   */
  async createOrder(orderData) {
    try {
      const { items, total, tableId, comment, clientId, isFirstOrder, customerData } = orderData;

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

      // Формируем комментарий с номером стола, именем гостя и комментарием
      let orderComment = '';
      if (tableId) {
        orderComment += `Стол: ${tableId}`;
      }
      if (orderData.customerData && orderData.customerData.name) {
        orderComment += orderComment ? ' | ' : '';
        orderComment += `Гость: ${orderData.customerData.name}`;
      }
      if (comment) {
        orderComment += orderComment ? ' | ' : '';
        orderComment += `Комментарий: ${comment}`;
      }

      // Подготавливаем данные заказа согласно документации
      const orderPayload = {
        spot_id: tableId || 1, // ID стола/места
        products: products,
        comment: orderComment || ''
      };

      // Привязываем заказ к клиенту по client_id
      if (clientId) {
        orderPayload.client_id = clientId;
      }

      // Добавляем скидку 20% если это первый заказ согласно документации
      if (isFirstOrder) {
        orderPayload.discounts = [
          { "type": "percent", "value": 20 }
        ];
      }

      console.log('Creating order with payload:', JSON.stringify(orderPayload, null, 2));
      
      const response = await axios.post(
        `${this.baseUrl}/incomingOrders.createIncomingOrder?token=${this.getToken()}`,
        orderPayload,
        {
          headers: {
            'Content-Type': 'application/json'
          }
        }
      );
      
      console.log('Order creation response:', response.data);

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

      // Сначала создаем минимального клиента с правильным именем
      const clientData = {
        name: customerData.name || 'Гость',
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
        customerData: clientData, // Передаем данные клиента для комментария
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
