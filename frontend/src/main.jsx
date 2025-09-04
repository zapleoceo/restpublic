import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

console.log('🚀 React app starting...');

// Функция инициализации React
function initReactApp() {
  console.log('🔍 Initializing React app...');
  
  // Ищем элемент #root для рендеринга React приложения
  const reactContainer = document.getElementById('root');
  if (!reactContainer) {
    console.error('❌ Root element not found!');
    return;
  }

  console.log('✅ Found #root element:', reactContainer);

  try {
    const root = createRoot(reactContainer);
    console.log('✅ Root created successfully');
    
    // Рендерим основное приложение
    root.render(
      <StrictMode>
        <App />
      </StrictMode>,
    );
    console.log('✅ Main App rendered successfully');
    
  } catch (error) {
    console.error('❌ Error rendering React app:', error);
    console.error('Error stack:', error.stack);
  }
}

// Ждем полной загрузки всех ресурсов, включая template JS
if (document.readyState === 'complete') {
  // Если страница уже загружена, инициализируем сразу
  console.log('📄 Document already loaded, initializing React...');
  initReactApp();
} else {
  // Ждем полной загрузки страницы (включая все скрипты)
  console.log('⏳ Waiting for full page load...');
  window.addEventListener('load', function() {
    console.log('📄 Full page loaded, initializing React...');
    // Небольшая задержка для гарантии, что template JS полностью выполнился
    setTimeout(initReactApp, 100);
  });
}
