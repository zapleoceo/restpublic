import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

console.log('React app starting...');

// Создаем новый контейнер для React, чтобы избежать конфликтов с шаблоном
let reactContainer = document.getElementById('react-app');
if (!reactContainer) {
  reactContainer = document.createElement('div');
  reactContainer.id = 'react-app';
  reactContainer.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    background: white;
    overflow-y: auto;
  `;
  document.body.appendChild(reactContainer);
  console.log('Created new React container:', reactContainer);
}

try {
  const root = createRoot(reactContainer);
  console.log('Root created successfully in new container');
  
  // Сначала рендерим простой тест
  root.render(
    <StrictMode>
      <div style={{ 
        padding: '20px', 
        backgroundColor: 'red', 
        color: 'white', 
        fontSize: '24px',
        position: 'relative',
        zIndex: '10001'
      }}>
        🎉 REACT APP IS WORKING! 🎉
      </div>
    </StrictMode>,
  );
  console.log('Test component rendered successfully');
  
  // Через 2 секунды рендерим основное приложение
  setTimeout(() => {
    root.render(
      <StrictMode>
        <App />
      </StrictMode>,
    );
    console.log('Main App rendered successfully');
  }, 2000);
  
} catch (error) {
  console.error('Error rendering React app:', error);
}
