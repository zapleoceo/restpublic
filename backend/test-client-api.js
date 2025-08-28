const axios = require('axios');
require('dotenv').config();

const token = process.env.POSTER_API_TOKEN;
const baseUrl = 'https://joinposter.com/api';

async function testCreateClient() {
  try {
    console.log('Testing client creation API...');
    console.log('Token exists:', !!token);
    
    const clientData = {
      client_name: 'Test',
      client_lastname: 'User',
      client_phone: '+380991234567',
      client_birthday: '',
      client_sex: 0,
      client_groups_id_client: 1
    };

    console.log('Sending request with data:', clientData);
    
    // Создаем URLSearchParams для form data
    const formData = new URLSearchParams();
    formData.append('client_name', clientData.client_name);
    formData.append('client_lastname', clientData.client_lastname);
    formData.append('client_phone', clientData.client_phone);
    formData.append('client_birthday', clientData.client_birthday);
    formData.append('client_sex', clientData.client_sex);
    formData.append('client_groups_id_client', clientData.client_groups_id_client);
    
    const response = await axios.post(`${baseUrl}/clients.createClient?token=${token}`, formData, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    });

    console.log('Response status:', response.status);
    console.log('Response data:', JSON.stringify(response.data, null, 2));
    
    if (response.data && response.data.response) {
      console.log('✅ Client created successfully');
      return response.data.response;
    } else {
      console.log('❌ Unexpected response format');
      return null;
    }
  } catch (error) {
    console.error('❌ Error creating client:');
    console.error('Status:', error.response?.status);
    console.error('Data:', error.response?.data);
    console.error('Message:', error.message);
    return null;
  }
}

testCreateClient();
