import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Header } from './components/layout/Header';
import { HomePage } from './pages/HomePage';
import './i18n';
import './styles/globals.css';

function App() {
  console.log('App component rendering...');
  
  try {
    return (
      <Router>
        <div className="App">
          <Header />
          <Routes>
            <Route path="/" element={<HomePage />} />
            {/* Добавьте другие маршруты здесь */}
          </Routes>
        </div>
      </Router>
    );
  } catch (error) {
    console.error('Error in App component:', error);
    return <div>Error loading app</div>;
  }
}

export default App;
