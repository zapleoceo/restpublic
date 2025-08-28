const axios = require('axios');

class OrderService {
  constructor() {
    this.baseUrl = 'https://joinposter.com/api';
    this.token = process.env.POSTER_API_TOKEN;
    console.log('OrderService initialized with token:', this.token ? 'present' : 'missing');
  }

  /**
   * Проверить существующего клиента по номеру телефона
   */
  async checkExistingClient(phone) {
    try {
      console.log('Checking existing client with phone:', phone);
      const response = await axios.get(`${this.baseUrl}/clients.getClients?token=${this.token}`);

      console.log('Client check response:', response.data);
      
      if (response.data && response.data.response) {
        // Фильтруем клиентов по номеру телефона
        const client = response.data.response.find(c => 
          c.phone === phone || c.phone_number === phone.replace(/\D/g, '')
        );
        
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
      const response = await axios.post(`${this.baseUrl}/clients.createClient?token=${this.token}`, {
        client_name: clientData.name,
        client_lastname: clientData.lastName || '',
        client_phone: clientData.phone,
        client_birthday: clientData.birthday || '',
        client_sex: clientData.gender === 'male' ? 1 : (clientData.gender === 'female' ? 2 : 0),
        client_groups_id_client: 2 // Обязательное поле - группа клиентов (Founders)
      }, {
        headers: {
          'Content-Type': 'application/json'
        }
      });

      console.log('Client creation response:', response.data);

      if (response.data && response.data.response) {
        return response.data.response;
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
        `${this.baseUrl}/dash.getTransactions?token=${this.token}&dateFrom=${dateFrom}&dateTo=${dateTo}`
      );

      console.log('First order check response:', response.data);

      // Фильтруем транзакции по client_id
      if (response.data && response.data.response) {
        const clientTransactions = response.data.response.filter(t => 
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

      // Подготавливаем данные заказа
      const orderPayload = {
        spot_id: tableId || 1, // ID стола/места
        client_id: clientId,
        products: products,
        comment: comment || ''
      };

      // Добавляем телефон клиента (обязательное поле)
      if (customerData && customerData.phone) {
        orderPayload.client_phone = customerData.phone;
      }

      // Добавляем скидку 20% если это первый заказ
      if (isFirstOrder) {
        orderPayload.discount = 20; // Процентная скидка
      }

      // Убираем лишние поля, которые могут вызывать ошибки
      // client_phone не нужен, так как client_id уже содержит всю информацию о клиенте

      console.log('Creating order with payload:', JSON.stringify(orderPayload, null, 2));
      
      const response = await axios.post(
        `${this.baseUrl}/incomingOrders.createIncomingOrder?token=${this.token}`,
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
