import kekaLogo from '../assets/keka.png';
import zohoLogo from '../assets/zoho.png';
import { useEffect, useState } from 'react';
import AgendaCalendar from './AgendaCalendar';

export default function Dashboard() {
  const [user, setUser] = useState(null);

  useEffect(() => {
    fetch('/auth/status', { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
        if (data.isAuthenticated && data.user && data.user.profile) {
          setUser(data.user.profile);
        }
      });
  }, []);

  return (
    <div className="flex flex-col gap-4 w-full h-full px-8 py-6">
      <h1 className="text-3xl font-bold mb-2">
        Welcome back{user ? `, ${user.displayName}` : ', employee'}
      </h1>
      <div className="flex gap-8 mb-4">
        <div
          className="bg-[#115948] rounded-3xl flex items-center justify-center transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95 group"
          style={{ width: '210px', height: '144px' }}
        >
          <img src={kekaLogo} alt="Keka" className="h-16 object-contain transition duration-200 group-hover:scale-110" />
        </div>
        <div
          className="bg-[#115948] rounded-3xl flex items-center justify-center transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95 group"
          style={{ width: '210px', height: '144px' }}
        >
          <img src={zohoLogo} alt="Zoho" className="h-16 object-contain transition duration-200 group-hover:scale-110" />
        </div>
      </div>
      <div
        className="bg-[#115948] rounded-3xl flex-grow p-4 overflow-auto"
        style={{ maxHeight: '444px' }}
      >
        <AgendaCalendar />
      </div>
    </div>
  );
} 