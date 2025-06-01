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

export default function AgendaCalendar() {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    fetch('/auth/status', { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
        const calendarData = data.user && data.user.calendar;
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
          setError('No agenda events found.');
        }
        setLoading(false);
      })
      .catch((err) => {
        setError('Failed to fetch agenda events.');
        setLoading(false);
        console.error('Agenda fetch error:', err);
      });
  }, []);

  return (
    <div className="w-full h-full bg-white rounded-2xl shadow-lg p-4 overflow-auto">
      {loading ? (
        <div>Loading agenda events...</div>
      ) : error ? (
        <div className="text-red-600 text-center my-8">{error}</div>
      ) : (
        <BigCalendar
          localizer={localizer}
          events={events}
          startAccessor="start"
          endAccessor="end"
          style={{ height: 400 }}
          views={['agenda']}
          defaultView="agenda"
          popup
        />
      )}
    </div>
  );
} 