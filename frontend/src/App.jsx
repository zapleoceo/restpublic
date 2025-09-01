import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Header } from './components/layout/Header';
import { HomePage } from './pages/HomePage';
import './i18n';
import './styles/globals.css';

function App() {
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
}

export default App;
