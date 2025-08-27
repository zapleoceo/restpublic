#!/usr/bin/env node

const http = require('http');
const { exec } = require('child_process');
const crypto = require('crypto');

const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET || 'your-secret-key';
const DEPLOY_PATH = '/var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com';

const server = http.createServer((req, res) => {
  if (req.method === 'POST' && req.url === '/deploy') {
    let body = '';
    
    req.on('data', chunk => {
      body += chunk.toString();
    });
    
    req.on('end', () => {
      try {
        const signature = req.headers['x-hub-signature-256'];
        const expectedSignature = 'sha256=' + crypto
          .createHmac('sha256', WEBHOOK_SECRET)
          .update(body)
          .digest('hex');
        
        if (signature !== expectedSignature) {
          res.writeHead(401);
          res.end('Unauthorized');
          return;
        }
        
        const payload = JSON.parse(body);
        
        // Проверяем, что это push в main ветку
        if (payload.ref === 'refs/heads/main') {
          console.log('🚀 Запускаем автодеплой North Republic...');
          
          exec(`cd ${DEPLOY_PATH} && ./deploy.sh`, (error, stdout, stderr) => {
            if (error) {
              console.error('❌ Ошибка деплоя:', error);
              res.writeHead(500);
              res.end('Deploy failed');
              return;
            }
            
            console.log('✅ Деплой North Republic завершен успешно');
            console.log(stdout);
            
            res.writeHead(200);
            res.end('Deploy successful');
          });
        } else {
          res.writeHead(200);
          res.end('Ignored - not main branch');
        }
      } catch (error) {
        console.error('❌ Ошибка обработки webhook:', error);
        res.writeHead(400);
        res.end('Bad request');
      }
    });
  } else {
    res.writeHead(404);
    res.end('Not found');
  }
});

const PORT = process.env.PORT || 3002;
server.listen(PORT, () => {
  console.log(`🚀 Webhook сервер запущен на порту ${PORT}`);
  console.log(`📡 Webhook URL: http://your-server:${PORT}/deploy`);
});
