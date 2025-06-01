import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import RightPanel from './components/RightPanel';
import Login from './components/Login';
import { useEffect, useState } from 'react';
import Calendar from './components/Calendar';
import DocumentDirectory from './components/DocumentDirectory';
import '../css/globals.css';

function App() {
  const [isAuthenticated, setIsAuthenticated] = useState(null);
  const [showCalendar, setShowCalendar] = useState(false);
  const [showDocs, setShowDocs] = useState(false);

  useEffect(() => {
    fetch('/auth/status', {
      credentials: 'include'
    })
      .then(res => res.json())
      .then(data => setIsAuthenticated(data.isAuthenticated))
      .catch(() => setIsAuthenticated(false));
  }, []);

  if (isAuthenticated === null) {
    return <div>Loading...</div>;
  }

  if (!isAuthenticated) {
    return (
      <Router>
        <Routes>
          <Route path="*" element={<Login />} />
        </Routes>
      </Router>
    );
  }

  return (
    <Router>
      <div className="flex h-screen bg-[#ffffff]">
        <Sidebar onCalendarClick={() => setShowCalendar(true)} />
        <div className="flex-grow">
          <Dashboard />
        </div>
        <RightPanel onShowDocs={() => setShowDocs(true)} />
        {showCalendar && <Calendar onClose={() => setShowCalendar(false)} />}
        {showDocs && <DocumentDirectory onClose={() => setShowDocs(false)} />}
      </div>
    </Router>
  );
}

export default App;
