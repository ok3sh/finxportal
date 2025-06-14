import { useEffect, useState } from 'react';
import { Calendar as BigCalendar, dateFnsLocalizer } from 'react-big-calendar';
import format from 'date-fns/format';
import parse from 'date-fns/parse';
import startOfWeek from 'date-fns/startOfWeek';
import getDay from 'date-fns/getDay';
import enUS from 'date-fns/locale/en-US';
import 'react-big-calendar/lib/css/react-big-calendar.css';

const locales = {
  'en-US': enUS,
};

const localizer = dateFnsLocalizer({
  format,
  parse,
  startOfWeek: () => startOfWeek(new Date(), { weekStartsOn: 0 }),
  getDay,
  locales,
});

export default function Calendar({ onClose }) {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch events function
  const fetchEvents = async (refresh = false) => {
    setLoading(true);
    setError(null);
    
    try {
      const url = refresh ? '/api/auth/calendar/refresh' : '/api/auth/status';
      const response = await fetch(url, { credentials: 'include' });
      
      if (!response.ok) {
        if (response.status === 401) {
          setError('Authentication required. Please log in again.');
          return;
        } else if (response.status === 500) {
          setError('Calendar service temporarily unavailable. Microsoft Graph API may be down.');
          return;
        } else {
          setError(`Calendar service error (${response.status}). Please try again.`);
          return;
        }
      }

      const data = await response.json();
      console.log('Calendar response:', data); // Debug log
      
      // Handle different response formats
      let calendarData = null;
      
      if (refresh) {
        // Refresh endpoint: data.calendar (direct from Graph API)
        calendarData = data.calendar;
        // If it's a Graph API response, extract the value array
        if (calendarData && calendarData.value) {
          calendarData = calendarData.value;
        }
      } else {
        // Status endpoint: data.user.calendar (from session)
        if (!data.isAuthenticated) {
          setError('Not authenticated. Please log in again.');
          return;
        }
        calendarData = data.user && data.user.calendar;
        // If it's a Graph API response stored in session, extract the value array
        if (calendarData && calendarData.value) {
          calendarData = calendarData.value;
        }
      }
      
      if (Array.isArray(calendarData) && calendarData.length > 0) {
        const mapped = calendarData.map(ev => ({
          id: ev.id,
          title: ev.subject || 'No Title',
          start: new Date(ev.start?.dateTime),
          end: new Date(ev.end?.dateTime),
          allDay: false,
          location: ev.location?.displayName || '',
        }));
        setEvents(mapped);
        setError(null); // Clear any previous errors
      } else {
        console.log('No calendar data found, calendarData:', calendarData);
        setError('No upcoming calendar events found.');
      }
      
    } catch (err) {
      console.error('Calendar fetch error:', err);
      if (err.name === 'TypeError' && err.message.includes('fetch')) {
        setError('Unable to connect to calendar service. Please check your internet connection.');
      } else {
        setError('Failed to fetch calendar events. Please try refreshing.');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEvents();
  }, []);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 backdrop-blur-sm">
      {/* Modal header with close and refresh buttons */}
      <div className="absolute top-8 right-8 flex gap-2 z-60">
        {/* New refresh button using Unicode symbol for maximum compatibility */}
        <button
          onClick={() => fetchEvents(true)}
          className="flex items-center justify-center w-10 h-10 bg-white text-black hover:bg-gray-200 rounded-full shadow-lg border border-gray-300 transition-colors duration-150"
          aria-label="Refresh calendar events"
          disabled={loading}
          style={{ fontSize: '1.5rem', lineHeight: 1, padding: 0 }}
        >
          {loading ? '⟲' : '⟳'}
        </button>
        <button
          className="flex items-center justify-center w-10 h-10 bg-white text-gray-700 hover:bg-gray-200 rounded-full shadow-lg border border-gray-300 transition-colors duration-150"
          onClick={onClose}
          style={{ fontSize: '2rem', lineHeight: 1, padding: 0 }}
          aria-label="Close calendar modal"
        >
          &times;
        </button>
      </div>
      <div className="bg-white rounded-xl shadow-2xl p-6 w-full max-w-2xl relative">
        {loading ? (
          <div className="flex items-center justify-center py-8">
            <div className="text-gray-600">Loading calendar events...</div>
          </div>
        ) : error ? (
          <div className="text-center py-8">
            <div className="text-red-600 mb-4">{error}</div>
            {error.includes('Authentication') || error.includes('log in') ? (
              <button
                onClick={() => window.location.href = '/auth/login'}
                className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
              >
                Log In Again
              </button>
            ) : (
              <button
                onClick={() => fetchEvents(true)}
                className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
                disabled={loading}
              >
                Try Again
              </button>
            )}
          </div>
        ) : (
          <BigCalendar
            localizer={localizer}
            events={events}
            startAccessor="start"
            endAccessor="end"
            style={{ height: 500 }}
            popup
          />
        )}
      </div>
    </div>
  );
} 