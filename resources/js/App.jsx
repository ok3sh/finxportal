import { BrowserRouter as Router } from 'react-router-dom';
import AppRoutes from './AppRoutes';
import { useEffect, useState } from 'react';

function App() {
  const [isAuthenticated, setIsAuthenticated] = useState(null);
  const [authError, setAuthError] = useState(null);

  useEffect(() => {
    console.log('🔄 App: Checking authentication...');
    
    // Using /api/auth/status as this route moved to api.php
    fetch('/api/auth/status', {
      credentials: 'include'
    })
      .then(res => {
        console.log('🔄 App: Auth status response:', res.status);
        return res.json();
      })
      .then(data => {
        console.log('✅ App: Auth data:', data);
        setIsAuthenticated(data.isAuthenticated);
        setAuthError(null);
      })
      .catch(error => {
        console.error('❌ App: Auth check failed:', error);
        setIsAuthenticated(false);
        setAuthError(error.message);
      });
  }, []);

  // Show loading state
  if (isAuthenticated === null) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#115948] mx-auto mb-4"></div>
          <p className="text-gray-600">Loading portal...</p>
          {authError && (
            <p className="text-red-500 text-sm mt-2">Error: {authError}</p>
          )}
        </div>
      </div>
    );
  }

  console.log('🚀 App: Rendering AppRoutes with isAuthenticated:', isAuthenticated);

  return (
    <Router>
      <AppRoutes isAuthenticated={isAuthenticated} />
    </Router>
  );
}

export default App;
