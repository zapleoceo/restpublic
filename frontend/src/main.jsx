import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

console.log('React app starting...');
console.log('Root element:', document.getElementById('root'));

try {
  const root = createRoot(document.getElementById('root'));
  console.log('Root created successfully');
  
  // Сначала рендерим простой тест
  root.render(
    <StrictMode>
      <div style={{ padding: '20px', backgroundColor: 'red', color: 'white', fontSize: '24px' }}>
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
