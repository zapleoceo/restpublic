import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import HomePage from './pages/HomePage';
import VersionInfo from './components/VersionInfo';
import './App.css';

function App() {
  console.log('App component rendering...');
  
  return (
    <Router>
      <div className="App">
        <VersionInfo />
        <Routes>
          <Route path="/" element={<HomePage />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
