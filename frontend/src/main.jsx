import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

console.log('React app starting...');

// Ищем элемент #page для рендеринга React приложения
let reactContainer = document.getElementById('root');
if (!reactContainer) {
  // Если нет #root, создаем его в начале body
  reactContainer = document.createElement('div');
  reactContainer.id = 'root';
  reactContainer.style.cssText = `
    position: relative;
    z-index: 10000;
    background: white;
    min-height: 100vh;
  `;
  document.body.insertBefore(reactContainer, document.body.firstChild);
  console.log('Created #root element');
} else {
  // Очищаем root от старого контента
  reactContainer.innerHTML = '';
  reactContainer.style.cssText = `
    position: relative;
    z-index: 10000;
    background: white;
    min-height: 100vh;
  `;
  console.log('Using existing #root element');
}

try {
  const root = createRoot(reactContainer);
  console.log('Root created successfully');
  
  // Рендерим основное приложение
  root.render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
  console.log('Main App rendered successfully');
  
} catch (error) {
  console.error('Error rendering React app:', error);
}
