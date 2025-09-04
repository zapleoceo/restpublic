import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

console.log('React app starting...');

// Ищем элемент #root для рендеринга React приложения
const reactContainer = document.getElementById('root');
if (!reactContainer) {
  console.error('Root element not found!');
  return;
}

console.log('Found #root element:', reactContainer);

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
