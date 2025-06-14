import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

function SupportIcon() {
  return (
    <svg fill="#ffffff" viewBox="0 0 24 24" width={24} height={24} xmlns="http://www.w3.org/2000/svg">
      <g><g><path d="M12,2v7.1c1.2,0.4,2,1.5,2,2.8c0,0.5-0.1,1-0.4,1.4l2,1.6c0.1,0,0.2-0.1,0.4-0.1c0.6,0,1,0.4,1,1c0,0.6-0.4,1-1,1 c-0.6,0-1-0.4-1-1v-0.1l-2-1.6c-0.5,0.5-1.2,0.8-2,0.8c-1.7,0-3-1.3-3-3c0-1.3,0.8-2.4,2-2.8v-7H9.9C6.4,2.5,3.5,5.4,3.1,9 c-0.3,2.2,0.3,4.2,1.5,5.8C5.5,16,6,17.3,6,18.8V22h9v-3h2c1.1,0,2-0.9,2-2v-3l1.5-0.6c0.4-0.2,0.6-0.8,0.4-1.2l-1.9-3 C18.6,5.5,15.7,2.5,12,2z M11,10.5c-0.8,0-1.5,0.7-1.5,1.5s0.7,1.5,1.5,1.5s1.5-0.7,1.5-1.5S11.8,10.5,11,10.5z"/></g><rect fill="none" width="24" height="24"/></g>
    </svg>
  );
}

export default function RightPanel({ onShowDocs }) {
  const [user, setUser] = useState(null);
  const [rightPanelLink, setRightPanelLink] = useState({
    name: 'Outlook',
    url: 'https://outlook.office.com',
    is_personalized: false
  });
  const navigate = useNavigate();

  useEffect(() => {
    // Fetch user status
    fetch('/api/auth/status', { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
        if (data.isAuthenticated && data.user && data.user.profile) {
          setUser(data.user.profile);
        }
      });
      
    // Fetch personalized RightPanel link
    fetch('/api/links/rightpanel', { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
        setRightPanelLink({
          name: data.name || 'Outlook',
          url: data.url || 'https://outlook.office.com',
          is_personalized: data.is_personalized || false
        });
      })
      .catch(error => {
        console.error('Failed to fetch rightpanel link:', error);
        // Keep default Outlook link on error
      });
  }, []);

  // Microsoft Graph photo endpoint: /me/photo/$value (not fetched yet)
  // Placeholder for now, can be replaced with actual photo fetch if needed
  const profilePic = user && user.userPrincipalName
    ? `https://ui-avatars.com/api/?name=${encodeURIComponent(user.displayName || user.userPrincipalName)}&background=115948&color=fff&size=128`
    : null;

  const handleRightPanelLinkClick = (e) => {
    e.preventDefault();
    // Open link in new tab to preserve main portal session
    window.open(rightPanelLink.url, '_blank');
  };

  const handleSupportClick = () => {
    navigate('/memo-approval');
  };

  return (
    <div className="bg-[#115948] w-72 h-full rounded-l-3xl flex flex-col justify-between items-center py-8 px-4">
      <div className="flex flex-col items-center w-full">
        <div className="bg-yellow-100 rounded-full w-28 h-28 mb-4 flex items-center justify-center overflow-hidden border-4 border-white">
          {profilePic && <img src={profilePic} alt="Profile" className="w-28 h-28 object-cover" />}
        </div>
        <div className="text-white text-2xl font-bold">{user ? user.displayName : 'employee'}</div>
        <div className="text-white text-xs font-semibold mb-28">{user ? user.jobTitle : 'HR Manager'}</div>
        <div className="flex flex-col items-center w-full px-8 gap-6">
          <a
            href="#"
            onClick={e => { e.preventDefault(); onShowDocs(); }}
            className="text-white text-lg transition duration-200 hover:scale-105 hover:text-green-200 active:scale-95"
          >
            Document directory
          </a>
          <hr className="border-green-200 w-full my-2" />
          <a 
            href="#" 
            onClick={handleRightPanelLinkClick}
            className={`text-white text-lg transition duration-200 hover:scale-105 hover:text-green-200 active:scale-95 ${
              rightPanelLink.is_personalized ? 'font-semibold' : ''
            }`}
            title={rightPanelLink.is_personalized ? `Personalized link: ${rightPanelLink.url}` : 'Default Outlook link'}
          >
            {rightPanelLink.name}
          </a>
        </div>
      </div>
      <div className="w-full flex justify-center mb-2">
        <button 
          onClick={handleSupportClick}
          className="bg-[#177761] rounded-xl flex items-center gap-8 pl-3 pr-16 py-3 text-white font-semibold text-lg shadow hover:bg-[#115948] transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95"
        >
          <SupportIcon /> support
        </button>
      </div>
    </div>
  );
} 