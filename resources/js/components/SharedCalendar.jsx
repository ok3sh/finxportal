import { useEffect, useState } from 'react';

export default function SharedCalendar() {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchSharedEvents();
  }, []);

  const fetchSharedEvents = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch('/api/shared-calendar', { credentials: 'include' });
      const data = await response.json();
      
      if (data.events && Array.isArray(data.events)) {
        setEvents(data.events);
      } else {
        setError('No shared calendar events available');
      }
    } catch (error) {
      console.error('SharedCalendar fetch error:', error);
      setError('Failed to fetch shared calendar events');
    } finally {
      setLoading(false);
    }
  };

  const formatEventTime = (event) => {
    try {
      const startTime = new Date(event.start?.dateTime || event.start);
      const endTime = new Date(event.end?.dateTime || event.end);
      
      const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
      };
      
      const start = startTime.toLocaleTimeString([], timeOptions);
      const end = endTime.toLocaleTimeString([], timeOptions);
      
      return `${start} - ${end}`;
    } catch (error) {
      return 'Time TBD';
    }
  };

  const formatEventDate = (event) => {
    try {
      const eventDate = new Date(event.start?.dateTime || event.start);
      const today = new Date();
      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);
      
      // Check if it's today
      if (eventDate.toDateString() === today.toDateString()) {
        return 'Today';
      }
      
      // Check if it's tomorrow
      if (eventDate.toDateString() === tomorrow.toDateString()) {
        return 'Tomorrow';
      }
      
      // Otherwise show date
      return eventDate.toLocaleDateString([], { 
        month: 'short', 
        day: 'numeric' 
      });
    } catch (error) {
      return 'Date TBD';
    }
  };

  return (
    <div className="w-full h-full bg-white rounded-2xl shadow-lg p-4 overflow-auto">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold text-gray-800">Company Announcements</h3>
        <button
          onClick={fetchSharedEvents}
          className="text-gray-500 hover:text-gray-700 transition-colors"
          disabled={loading}
          title="Refresh shared calendar"
        >
          <span className={`inline-block ${loading ? 'animate-spin' : ''}`}>⟳</span>
        </button>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-8">
          <div className="text-gray-500">Loading shared events...</div>
        </div>
      ) : error ? (
        <div className="text-center py-8">
          <div className="text-gray-500 text-sm">{error}</div>
        </div>
      ) : events.length === 0 ? (
        <div className="text-center py-8">
          <div className="text-gray-500 text-sm">No shared events available</div>
        </div>
      ) : (
        <div className="space-y-3">
          {events.map((event, index) => (
            <div
              key={event.id || index}
              className="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400 hover:bg-gray-100 transition-colors"
            >
              <div className="font-medium text-gray-800 text-sm truncate">
                {event.subject || 'Untitled Event'}
              </div>
              <div className="text-xs text-gray-600 mt-1">
                {formatEventDate(event)} • {formatEventTime(event)}
              </div>
              {event.location?.displayName && (
                <div className="text-xs text-gray-500 mt-1 flex items-center">
                  <span className="mr-1">📍</span>
                  {event.location.displayName}
                </div>
              )}
              {event.organizer?.emailAddress?.name && (
                <div className="text-xs text-gray-500 mt-1">
                  by {event.organizer.emailAddress.name}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
} 