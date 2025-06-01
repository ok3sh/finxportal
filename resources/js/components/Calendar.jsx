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
  const fetchEvents = (refresh = false) => {
    setLoading(true);
    setError(null);
    const url = refresh
      ? '/auth/calendar/refresh'
      : '/auth/status';
    fetch(url, { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
        // For refresh endpoint, data.calendar; for status, data.user.calendar
        const calendarData = data.calendar || (data.user && data.user.calendar);
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
        } else {
          setError('No calendar events found for your account.');
        }
        setLoading(false);
      })
      .catch((err) => {
        setError('Failed to fetch calendar events.');
        setLoading(false);
        console.error('Calendar fetch error:', err);
      });
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
          ⟳
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
          <div>Loading calendar events...</div>
        ) : error ? (
          <div className="text-red-600 text-center my-8">{error}</div>
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