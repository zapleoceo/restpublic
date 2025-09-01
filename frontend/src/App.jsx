import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Header } from './components/layout/Header';
import { HomePage } from './pages/HomePage';
import './i18n';
import './styles/globals.css';

function App() {
  return (
    <Router>
      <div id="top">
        {/* Preloader */}
        <div id="preloader">
          <div id="loader" className="dots-fade">
            <div></div>
            <div></div>
            <div></div>
          </div>
        </div>

        {/* Page wrap */}
        <div id="page" className="s-pagewrap">
          <Header />
          
          <Routes>
            <Route path="/" element={<HomePage />} />
            {/* Другие маршруты будут добавлены позже */}
          </Routes>
        </div>
      </div>
    </Router>
  );
}

export default App;
