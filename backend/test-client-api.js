const axios = require('axios');
require('dotenv').config();

const token = process.env.POSTER_API_TOKEN;
const baseUrl = 'https://api.joinposter.com';

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
      client_groups_id_client: 2
    };

    console.log('Sending request with data:', clientData);
    
    const response = await axios.post(`${baseUrl}/clients.createClient?token=${token}`, clientData, {
      headers: {
        'Content-Type': 'application/json'
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
