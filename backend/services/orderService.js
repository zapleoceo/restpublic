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
   * Найти клиента по номеру телефона
   */
  async findClientByPhone(phone) {
    try {
      console.log('Finding client with phone:', phone);
      
      // Форматируем номер телефона для поиска
      let formattedPhone = phone;
      if (formattedPhone.startsWith('+')) {
        formattedPhone = formattedPhone.substring(1);
      }
      formattedPhone = formattedPhone.replace(/\D/g, '');
      
      console.log('Searching for formatted phone:', formattedPhone);
      
      const response = await axios.get(`${this.baseUrl}/clients.getClients?token=${this.getToken()}`);

      console.log('Client search response:', response.data);
      
      if (response.data && response.data.response) {
        // Фильтруем клиентов по номеру телефона
        const client = response.data.response.find(c => {
          const clientPhone = c.phone || c.phone_number || '';
          const cleanClientPhone = clientPhone.replace(/\D/g, '');
          return cleanClientPhone === formattedPhone;
        });
        
        if (client) {
          return {
            client: client
          };
        }
      }

      return null;
    } catch (error) {
      console.error('Error finding client by phone:', error.response?.data || error.message);
      throw new Error('Ошибка при поиске клиента');
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
        // Poster API возвращает только ID клиента
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
        `${this.baseUrl}/dash.getTransactions?token=${this.getToken()}&dateFrom=${dateFrom}&dateTo=${dateTo}`
      );

      console.log('First order check response:', response.data);

             // Фильтруем транзакции по client_id
       if (response.data && response.data.response) {
         const clientTransactions = response.data.response.filter(t => 
           t.client_id.toString() === clientId.toString()
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
      const { items, total, tableId, comment, clientId, customerData, withRegistration } = orderData;

      // Проверяем, есть ли активный заказ за последние 6 часов
      const activeOrder = await this.findActiveOrder(clientId);
      
      if (activeOrder) {
        console.log(`🔄 Found active order ${activeOrder.transaction_id}, adding items to it`);
        
        // Добавляем товары к существующему заказу
        const updatedOrder = await this.addToExistingOrder(activeOrder.transaction_id, items);
        
        return {
          order: updatedOrder,
          isExistingOrder: true,
          existingOrderId: activeOrder.transaction_id
        };
      }

      // Если активного заказа нет, создаем новый
      console.log(`🆕 No active order found, creating new order`);

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

      // Добавляем скидку 20% если пользователь выбрал регистрацию
      if (withRegistration) {
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
        return {
          order: response.data.response,
          isExistingOrder: false
        };
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

      const clientId = await this.createClient(clientData);
      
      // Проверяем, есть ли активный заказ за последние 6 часов
      const activeOrder = await this.findActiveOrder(clientId);
      
      if (activeOrder) {
        console.log(`🔄 Found active order ${activeOrder.transaction_id}, adding items to it`);
        
        // Добавляем товары к существующему заказу
        const updatedOrder = await this.addToExistingOrder(activeOrder.transaction_id, items);
        
        return {
          order: updatedOrder,
          client: { client_id: clientId },
          isExistingOrder: true,
          existingOrderId: activeOrder.transaction_id
        };
      } else {
        console.log(`🆕 No active order found, creating new order`);
        
        // Создаем новый заказ
        const order = await this.createOrder({
          items,
          total,
          tableId,
          comment,
          clientId,
          customerData: clientData,
          withRegistration: false
        });

        return {
          order,
          client: { client_id: clientId },
          isExistingOrder: false
        };
      }
    } catch (error) {
      console.error('Error creating guest order:', error);
      throw new Error('Ошибка при создании заказа для гостя');
    }
  }

  /**
   * Получить заказы пользователя (неоплаченные)
   */
  async getUserOrders(userId) {
    try {
      console.log(`🔍 Fetching orders for user ${userId}`);
      
      // Сначала пробуем получить через transactions
      const dateFrom = '2025-08-01';
      const dateTo = new Date().toISOString().split('T')[0];
      
      const response = await axios.get(
        `${this.baseUrl}/dash.getTransactions?token=${this.getToken()}&dateFrom=${dateFrom}&dateTo=${dateTo}`
      );
      
      console.log(`📊 API response status: ${response.status}`);
      console.log(`📊 Total transactions in response: ${response.data?.response?.length || 0}`);
      console.log(`📊 First 5 transactions:`, response.data?.response?.slice(0, 5));
      
      if (response.data && response.data.response) {
                 // Фильтруем транзакции по client_id и статусу (неоплаченные)
         const userOrders = response.data.response.filter(order => {
           console.log(`🔍 Checking transaction: client_id=${order.client_id}, status=${order.status}, userId=${userId}`);
           return order.client_id.toString() === userId.toString() && 
                  (order.status === '0' || order.status === 0 || 
                   order.status === '1' || order.status === 1);
         });
        
        console.log(`✅ Found ${userOrders.length} unpaid orders for user ${userId}`);
        return userOrders;
      }
      
      console.log(`⚠️ No response data for user ${userId}`);
      return [];
    } catch (error) {
      console.error('Error fetching user orders:', error);
      throw new Error('Ошибка при получении заказов пользователя');
    }
  }

  /**
   * Получить детали заказа с товарами
   */
  async getOrderDetails(transactionId) {
    try {
      console.log(`🔍 Fetching order details for transaction ${transactionId}`);
      
      // Получаем детали транзакции
      const transactionResponse = await axios.get(
        `${this.baseUrl}/dash.getTransaction?token=${this.getToken()}&transaction_id=${transactionId}`
      );
      
      console.log(`📊 Transaction response:`, transactionResponse.data);
      
      if (!transactionResponse.data || !transactionResponse.data.response) {
        console.log(`⚠️ No transaction data for ${transactionId}`);
        return null;
      }
      
      const transaction = transactionResponse.data.response;
      
      // Получаем состав заказа через другой endpoint
      const productsResponse = await axios.get(
        `${this.baseUrl}/dash.getTransactionProducts?token=${this.getToken()}&transaction_id=${transactionId}`
      );
      
      console.log(`📊 Products response:`, productsResponse.data);
      
      // Объединяем данные
      const orderDetails = {
        ...transaction,
        products: productsResponse.data?.response || []
      };
      
      // Если products - это объект с ключами, преобразуем в массив
      if (orderDetails.products && typeof orderDetails.products === 'object' && !Array.isArray(orderDetails.products)) {
        orderDetails.products = Object.values(orderDetails.products);
      }
      
      // Нормализуем данные товаров
      if (orderDetails.products && Array.isArray(orderDetails.products)) {
        orderDetails.products = orderDetails.products.map(product => ({
          ...product,
          price: product.product_sum || product.price, // Используем product_sum как цену
          count: product.num || product.count // Используем num как количество
        }));
      }
      
      return orderDetails;
    } catch (error) {
      console.error('Error fetching order details:', error);
      throw new Error('Ошибка при получении деталей заказа');
    }
  }

  /**
   * Найти активный заказ пользователя (не старше 6 часов)
   */
  async findActiveOrder(userId) {
    try {
      console.log(`🔍 Finding active order for user ${userId}`);
      
      // Вычисляем время 6 часов назад
      const sixHoursAgo = new Date();
      sixHoursAgo.setHours(sixHoursAgo.getHours() - 6);
      const dateFrom = sixHoursAgo.toISOString().split('T')[0];
      const dateTo = new Date().toISOString().split('T')[0];
      
      const response = await axios.get(
        `${this.baseUrl}/dash.getTransactions?token=${this.getToken()}&dateFrom=${dateFrom}&dateTo=${dateTo}`
      );
      
      if (response.data && response.data.response) {
        // Ищем активные заказы (статус 0 или 1) для данного пользователя
        const activeOrders = response.data.response.filter(order => {
          const isUserOrder = order.client_id.toString() === userId.toString();
          const isActive = order.status === '0' || order.status === 0 || 
                          order.status === '1' || order.status === 1;
          const isRecent = new Date(parseInt(order.date_start)) > sixHoursAgo;
          
          console.log(`🔍 Checking order ${order.transaction_id}: user=${isUserOrder}, active=${isActive}, recent=${isRecent}`);
          
          return isUserOrder && isActive && isRecent;
        });
        
        // Возвращаем самый свежий заказ
        if (activeOrders.length > 0) {
          const latestOrder = activeOrders.reduce((latest, current) => {
            return parseInt(current.date_start) > parseInt(latest.date_start) ? current : latest;
          });
          
          console.log(`✅ Found active order ${latestOrder.transaction_id} for user ${userId}`);
          return latestOrder;
        }
      }
      
      console.log(`⚠️ No active orders found for user ${userId}`);
      return null;
    } catch (error) {
      console.error('Error finding active order:', error);
      return null;
    }
  }

  /**
   * Добавить товары к существующему заказу
   */
  async addToExistingOrder(transactionId, items) {
    try {
      console.log(`🔍 Adding items to existing order ${transactionId}:`, items);
      
      // Получаем текущие товары заказа
      const currentProductsResponse = await axios.get(
        `${this.baseUrl}/dash.getTransactionProducts?token=${this.getToken()}&transaction_id=${transactionId}`
      );
      
      let currentProducts = [];
      if (currentProductsResponse.data?.response) {
        // Если products - это объект с ключами, преобразуем в массив
        if (typeof currentProductsResponse.data.response === 'object' && !Array.isArray(currentProductsResponse.data.response)) {
          currentProducts = Object.values(currentProductsResponse.data.response);
        } else {
          currentProducts = currentProductsResponse.data.response;
        }
      }
      
      console.log(`📊 Current products in order:`, currentProducts);
      
      // Подготавливаем новые товары
      const newProducts = items.map(item => {
        let price = item.price;
        if (typeof price === 'object' && price !== null) {
          price = price['1'] || Object.values(price)[0] || 0;
        }
        price = parseFloat(price) || 0;

        return {
          product_id: item.product_id,
          count: item.quantity,
          price: Math.round(price * 100) // Poster API ожидает цену в копейках
        };
      });
      
      // Объединяем существующие и новые товары
      const allProducts = [...currentProducts, ...newProducts];
      
      // Группируем товары по product_id и суммируем количества
      const groupedProducts = {};
      allProducts.forEach(product => {
        const key = product.product_id;
        if (groupedProducts[key]) {
          groupedProducts[key].count += product.count;
        } else {
          groupedProducts[key] = { ...product };
        }
      });
      
      const finalProducts = Object.values(groupedProducts);
      console.log(`📦 Final products for order:`, finalProducts);
      
      // Обновляем заказ с новыми товарами
      const updatePayload = {
        products: finalProducts
      };
      
      const response = await axios.post(
        `${this.baseUrl}/incomingOrders.updateIncomingOrder?token=${this.getToken()}&incoming_order_id=${transactionId}`,
        updatePayload,
        {
          headers: {
            'Content-Type': 'application/json'
          }
        }
      );
      
      console.log('Order update response:', response.data);
      
      if (response.data && response.data.response) {
        return response.data.response;
      }
      
      throw new Error('Неверный ответ от API при обновлении заказа');
    } catch (error) {
      console.error('Error adding to existing order:', error);
      throw new Error('Ошибка при добавлении к существующему заказу: ' + (error.response?.data?.error || error.message));
    }
  }

  /**
   * Получить прошлые заказы пользователя с пагинацией
   */
  async getUserPastOrders(userId, limit = 10, offset = 0) {
    try {
      console.log(`🔍 Fetching past orders for user ${userId}, limit=${limit}, offset=${offset}`);
      
      // Получаем через transactions
      const dateFrom = '2025-08-01';
      const dateTo = new Date().toISOString().split('T')[0];
      
      const response = await axios.get(
        `${this.baseUrl}/dash.getTransactions?token=${this.getToken()}&dateFrom=${dateFrom}&dateTo=${dateTo}`
      );
      
      if (response.data && response.data.response) {
                 // Фильтруем транзакции по client_id и статусу (оплаченные или закрытые)
         const userOrders = response.data.response.filter(order => {
           console.log(`🔍 Checking past transaction: client_id=${order.client_id}, status=${order.status}, userId=${userId}`);
           return order.client_id.toString() === userId.toString() && 
                  (order.status === '2' || order.status === 2);
         });
        
        console.log(`✅ Found ${userOrders.length} past orders for user ${userId}`);
        
        // Применяем пагинацию
        const paginatedOrders = userOrders.slice(offset, offset + limit);
        console.log(`📄 Returning ${paginatedOrders.length} orders (paginated)`);
        
        return paginatedOrders;
      }
      
      console.log(`⚠️ No response data for past orders user ${userId}`);
      return [];
    } catch (error) {
      console.error('Error fetching user past orders:', error);
      throw new Error('Ошибка при получении прошлых заказов пользователя');
    }
  }
}

module.exports = new OrderService();
