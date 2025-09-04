import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

console.log('React app starting...');

// Принудительно показываем root элемент
const rootElement = document.getElementById('root');
if (rootElement) {
  rootElement.style.display = 'block';
  rootElement.style.visibility = 'visible';
  rootElement.style.opacity = '1';
  rootElement.style.zIndex = '9999';
  rootElement.style.position = 'relative';
  console.log('Root element found and made visible:', rootElement);
} else {
  console.error('Root element not found!');
}

try {
  const root = createRoot(rootElement);
  console.log('Root created successfully');
  
  // Сначала рендерим простой тест
  root.render(
    <StrictMode>
      <div style={{ 
        padding: '20px', 
        backgroundColor: 'red', 
        color: 'white', 
        fontSize: '24px',
        position: 'relative',
        zIndex: '10000'
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
