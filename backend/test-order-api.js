#!/usr/bin/env node

const https = require('https');
const fs = require('fs');
const path = require('path');

// Загружаем токен из .cursor/env.txt
const envPath = path.join(__dirname, '..', '.cursor', 'env.txt');
const envContent = fs.readFileSync(envPath, 'utf8');
const tokenMatch = envContent.match(/POSTER_API_TOKEN=([^\n]+)/);
const TOKEN = tokenMatch ? tokenMatch[1] : '';

console.log('🔑 Token:', TOKEN ? `${TOKEN.substring(0, 10)}...` : 'NOT FOUND');

function makeRequest(endpoint, data = {}) {
  return new Promise((resolve, reject) => {
    const postData = JSON.stringify(data);
    const url = `https://joinposter.com/api/${endpoint}?token=${TOKEN}`;
    
    console.log(`\n🧪 Testing: ${endpoint}`);
    console.log(`📍 URL: ${url}`);
    console.log(`📤 Data: ${postData}`);
    
    const urlObj = new URL(url);
    
    const options = {
      hostname: urlObj.hostname,
      port: 443,
      path: urlObj.pathname + urlObj.search,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      }
    };

    const req = https.request(options, (res) => {
      let responseData = '';
      
      res.on('data', (chunk) => {
        responseData += chunk;
      });
      
      res.on('end', () => {
        console.log(`✅ Status: ${res.statusCode}`);
        try {
          const parsed = JSON.parse(responseData);
          console.log(`📥 Response: ${JSON.stringify(parsed, null, 2)}`);
          resolve({ status: res.statusCode, data: parsed });
        } catch (e) {
          console.log(`📥 Raw Response: ${responseData}`);
          resolve({ status: res.statusCode, data: responseData });
        }
      });
    });

    req.on('error', (err) => {
      console.log(`❌ Error: ${err.message}`);
      reject(err);
    });

    req.write(postData);
    req.end();
  });
}

async function testOrderAPI() {
  console.log('🚀 Testing Order API methods...\n');
  
  // 1. Тест создания клиента согласно документации
  console.log('📋 Создание клиента...');
  const clientData = {
    client_name: 'Test User',
    client_lastname: 'API Test',
    client_phone: '+84' + Math.floor(Math.random() * 1000000000),
    client_birthday: '1990-01-01',
    client_sex: 1,
    client_groups_id_client: 2 // Правильное название поля
  };
  
  const clientResult = await makeRequest('clients.createClient', clientData);
  
  let clientId = null;
  if (clientResult.data && clientResult.data.response) {
    clientId = clientResult.data.response; // API возвращает только ID
    console.log(`✅ Created client with ID: ${clientId}`);
  }
  
  // 2. Тест создания заказа согласно документации
  if (clientId) {
    console.log('📋 Создание заказа...');
    const orderData = {
      spot_id: 1,
      client_id: clientId,
      products: [{
        product_id: 52, // ID продукта из предыдущих тестов
        count: 1,
        price: 2100000 // Цена в копейках
      }],
      comment: 'Test order from API',
      discount: 0
    };
    
    await makeRequest('incomingOrders.createIncomingOrder', orderData);
  }
  
  // 3. Тест создания чека (транзакции)
  console.log('📋 Создание чека...');
  const transactionData = {
    spot_id: 1,
    table_id: 9,
    guests_count: 1,
    products: [{
      product_id: 52,
      count: 1,
      price: 2100000
    }],
    pay_type: 1, // 1 - наличные, 2 - карта
    payed_cash: 2100000,
    payed_card: 0
  };
  
  await makeRequest('transactions.createTransaction', transactionData);
  
  console.log('\n✨ Order API testing completed!');
}

testOrderAPI().catch(console.error);
