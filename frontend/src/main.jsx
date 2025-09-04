import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

console.log('React app starting...');

// Используем стандартный #root элемент
const reactContainer = document.getElementById('root');
if (!reactContainer) {
  console.error('Root element not found!');
  return;
}

// Очищаем root от старого контента
reactContainer.innerHTML = '';
reactContainer.style.cssText = `
  position: relative;
  z-index: 10000;
  background: white;
  min-height: 100vh;
`;
console.log('Using standard root element:', reactContainer);

try {
  const root = createRoot(reactContainer);
  console.log('Root created successfully in new container');
  
  // Сначала рендерим простой тест с версией
  root.render(
    <StrictMode>
      <div style={{ 
        padding: '20px', 
        backgroundColor: 'red', 
        color: 'white', 
        fontSize: '24px',
        position: 'relative',
        zIndex: '10001',
        textAlign: 'center'
      }}>
        🎉 REACT APP IS WORKING! 🎉
        <br />
        <span style={{fontSize: '16px'}}>Version: 1.0.20</span>
        <br />
        <span style={{fontSize: '14px'}}>Container: #root</span>
        <br />
        <span style={{fontSize: '12px'}}>Build: {new Date().toISOString()}</span>
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
