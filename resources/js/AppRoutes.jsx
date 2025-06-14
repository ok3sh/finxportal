import { Routes, Route, useLocation, useNavigate } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import RightPanel from './components/RightPanel';
import Login from './components/Login';
import Calendar from './components/Calendar';
import DocumentDirectory from './components/DocumentDirectory';
import EmployeeDirectory from './components/EmployeeDirectory';
import MemoApproval from './components/MemoApproval';
import ITAdminTools from './components/ITAdminTools';
import HRAdminTools from './components/HRAdminTools';
import { useState, useEffect } from 'react';
import '../css/globals.css';

export default function AppRoutes({ isAuthenticated }) {
  const [showCalendar, setShowCalendar] = useState(false);
  const [showDocs, setShowDocs] = useState(false);
  const [showEmployeeDirectory, setShowEmployeeDirectory] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();

  useEffect(() => {
    console.log('🛤️ AppRoutes: Component mounted/updated with:', {
      isAuthenticated,
      pathname: location.pathname,
      showCalendar,
      showDocs,
      showEmployeeDirectory
    });
  }, [isAuthenticated, location.pathname, showCalendar, showDocs, showEmployeeDirectory]);

  if (!isAuthenticated) {
    console.log('🔒 AppRoutes: User not authenticated, showing login');
    return (
      <Routes>
        <Route path="*" element={<Login />} />
      </Routes>
    );
  }

  // Admin tools pages - full screen without sidebar/right panel
  if (location.pathname === '/it-admin-tools' || location.pathname === '/hr-admin-tools') {
    console.log('🔧 AppRoutes: Showing admin tools page');
    return (
      <Routes>
        <Route path="/it-admin-tools" element={<ITAdminTools />} />
        <Route path="/hr-admin-tools" element={<HRAdminTools />} />
      </Routes>
    );
  }

  console.log('🏠 AppRoutes: Rendering main layout with dashboard');

  // Default layout with sidebar and right panel always visible
  return (
    <div className="flex h-screen bg-[#ffffff]">
      <Sidebar 
        onCalendarClick={() => setShowCalendar(true)} 
        onEmployeeDirectoryClick={() => setShowEmployeeDirectory(true)}
      />
      <div className="flex-grow">
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/app" element={<Dashboard />} />
          <Route path="/memo-approval" element={<MemoApproval onClose={() => navigate('/')} />} />
        </Routes>
      </div>
      <RightPanel onShowDocs={() => setShowDocs(true)} />
      {showCalendar && <Calendar onClose={() => setShowCalendar(false)} />}
      {showDocs && <DocumentDirectory onClose={() => setShowDocs(false)} />}
      {showEmployeeDirectory && <EmployeeDirectory onClose={() => setShowEmployeeDirectory(false)} />}
    </div>
  );
} 