import React, { useState } from 'react';
import finLogo from '../assets/fin-logo.png';

function CalendarIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width={30} height={30}>
      <path d="M3 9H21M17 13.0014L7 13M10.3333 17.0005L7 17M7 3V5M17 3V5M6.2 21H17.8C18.9201 21 19.4802 21 19.908 20.782C20.2843 20.5903 20.5903 20.2843 20.782 19.908C21 19.4802 21 18.9201 21 17.8V8.2C21 7.07989 21 6.51984 20.782 6.09202C20.5903 5.71569 20.2843 5.40973 19.908 5.21799C19.4802 5 18.9201 5 17.8 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V17.8C3 18.9201 3 19.4802 3.21799 19.908C3.40973 20.2843 3.71569 20.5903 4.09202 20.782C4.51984 21 5.07989 21 6.2 21Z" stroke="#ffffff" strokeWidth="1.392" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  );
}

function PeopleIcon() {
  return (
    <svg width={30} height={30} viewBox="0 0 28.00 28.00" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#ffffff">
      <g id="SVGRepo_iconCarrier">
        <g id="🔍-Product-Icons" stroke="none" fill="none" fillRule="evenodd">
          <g id="ic_fluent_people_community_28_regular" fill="#ffffff" fillRule="nonzero">
            <path d="M17.25,18 C18.4926407,18 19.5,19.0073593 19.5,20.25 L19.5,21.7519766 L19.4921156,21.8604403 C19.1813607,23.9866441 17.2715225,25.0090369 14.0667905,25.0090369 C10.8736123,25.0090369 8.93330141,23.9983408 8.51446278,21.8965776 L8.5,21.75 L8.5,20.25 C8.5,19.0073593 9.50735931,18 10.75,18 L17.25,18 Z M17.25,19.5 L10.75,19.5 C10.3357864,19.5 10,19.8357864 10,20.25 L10,21.670373 C10.2797902,22.870787 11.550626,23.5090369 14.0667905,23.5090369 C16.582858,23.5090369 17.7966388,22.8777026 18,21.6931543 L18,20.25 C18,19.8357864 17.6642136,19.5 17.25,19.5 Z M18.2435553,11.9989081 L23.75,12 C24.9926407,12 26,13.0073593 26,14.25 L26,15.7519766 L25.9921156,15.8604403 C25.6813607,17.9866441 23.7715225,19.0090369 20.5667905,19.0090369 L20.2519278,19.0056708 L20.2519278,19.0056708 C19.9568992,18.2920884 19.4151086,17.7078172 18.7333573,17.3574924 C19.2481703,17.4584023 19.8580822,17.5090369 20.5667905,17.5090369 C23.082858,17.5090369 24.2966388,16.8777026 24.5,15.6931543 L24.5,14.25 C24.5,13.8357864 24.1642136,13.5 23.75,13.5 L18.5,13.5 C18.5,12.9736388 18.4096286,12.468385 18.2435553,11.9989081 Z M4.25,12 L9.75644465,11.9989081 C9.61805027,12.3901389 9.53222663,12.8062147 9.50746303,13.2386463 L9.5,13.5 L4.25,13.5 C3.83578644,13.5 3.5,13.8357864 3.5,14.25 L3.5,15.670373 C3.77979024,16.870787 5.05062598,17.5090369 7.5667905,17.5090369 C8.18886171,17.5090369 8.73132757,17.4704451 9.1985991,17.3944422 C8.5478391,17.7478373 8.03195873,18.3174175 7.74634871,19.0065739 L7.5667905,19.0090369 C4.37361228,19.0090369 2.43330141,17.9983408 2.01446278,15.8965776 L2,15.75 L2,14.25 C2,13.0073593 3.00735931,12 4.25,12 Z M14,10 C15.9329966,10 17.5,11.5670034 17.5,13.5 C17.5,15.4329966 15.9329966,17 14,17 C12.0670034,17 10.5,15.4329966 10.5,13.5 C10.5,11.5670034 12.0670034,10 14,10 Z M14,11.5 C12.8954305,11.5 12,12.3954305 12,13.5 C12,14.6045695 12.8954305,15.5 14,15.5 C15.1045695,15.5 16,14.6045695 16,13.5 C16,12.3954305 15.1045695,11.5 14,11.5 Z M20.5,4 C22.4329966,4 24,5.56700338 24,7.5 C24,9.43299662 22.4329966,11 20.5,11 C18.5670034,11 17,9.43299662 17,7.5 C17,5.56700338 18.5670034,4 20.5,4 Z M7.5,4 C9.43299662,4 11,5.56700338 11,7.5 C11,9.43299662 9.43299662,11 7.5,11 C5.56700338,11 4,9.43299662 4,7.5 C4,5.56700338 5.56700338,4 7.5,4 Z M20.5,5.5 C19.3954305,5.5 18.5,6.3954305 18.5,7.5 C18.5,8.6045695 19.3954305,9.5 20.5,9.5 C21.6045695,9.5 22.5,8.6045695 22.5,7.5 C22.5,6.3954305 21.6045695,5.5 20.5,5.5 Z M7.5,5.5 C6.3954305,5.5 5.5,6.3954305 5.5,7.5 C5.5,8.6045695 6.3954305,9.5 7.5,9.5 C8.6045695,9.5 9.5,8.6045695 9.5,7.5 C9.5,6.3954305 8.6045695,5.5 7.5,5.5 Z" id="🎨-Color"/>
          </g>
        </g>
      </g>
    </svg>
  );
}

function SettingsIcon() {
  return (
    <svg fill="#ffffff" viewBox="0 0 478.703 478.703" width={30} height={30} xmlns="http://www.w3.org/2000/svg">
      <g> <g> <path d="M454.2,189.101l-33.6-5.7c-3.5-11.3-8-22.2-13.5-32.6l19.8-27.7c8.4-11.8,7.1-27.9-3.2-38.1l-29.8-29.8 c-5.6-5.6-13-8.7-20.9-8.7c-6.2,0-12.1,1.9-17.1,5.5l-27.8,19.8c-10.8-5.7-22.1-10.4-33.8-13.9l-5.6-33.2 c-2.4-14.3-14.7-24.7-29.2-24.7h-42.1c-14.5,0-26.8,10.4-29.2,24.7l-5.8,34c-11.2,3.5-22.1,8.1-32.5,13.7l-27.5-19.8 c-5-3.6-11-5.5-17.2-5.5c-7.9,0-15.4,3.1-20.9,8.7l-29.9,29.8c-10.2,10.2-11.6,26.3-3.2,38.1l20,28.1 c-5.5,10.5-9.9,21.4-13.3,32.7l-33.2,5.6c-14.3,2.4-24.7,14.7-24.7,29.2v42.1c0,14.5,10.4,26.8,24.7,29.2l34,5.8 c3.5,11.2,8.1,22.1,13.7,32.5l-19.7,27.4c-8.4,11.8-7.1,27.9,3.2,38.1l29.8,29.8c5.6,5.6,13,8.7,20.9,8.7c6.2,0,12.1-1.9,17.1-5.5 l28.1-20c10.1,5.3,20.7,9.6,31.6,13l5.6,33.6c2.4,14.3,14.7,24.7,29.2,24.7h42.2c14.5,0,26.8-10.4,29.2-24.7l5.7-33.6 c11.3-3.5,22.2-8,32.6-13.5l27.7,19.8c5,3.6,11,5.5,17.2,5.5l0,0c7.9,0,15.3-3.1,20.9-8.7l29.8-29.8c10.2-10.2,11.6-26.3,3.2-38.1 l-19.8-27.8c5.5-10.5,10.1-21.4,13.5-32.6l33.6-5.6c14.3-2.4,24.7-14.7,24.7-29.2v-42.1 C478.9,203.801,468.5,191.501,454.2,189.101z M451.9,260.401c0,1.3-0.9,2.4-2.2,2.6l-42,7c-5.3,0.9-9.5,4.8-10.8,9.9 c-3.8,14.7-9.6,28.8-17.4,41.9c-2.7,4.6-2.5,10.3,0.6,14.7l24.7,34.8c0.7,1,0.6,2.5-0.3,3.4l-29.8,29.8c-0.7,0.7-1.4,0.8-1.9,0.8 c-0.6,0-1.1-0.2-1.5-0.5l-34.7-24.7c-4.3-3.1-10.1-3.3-14.7-0.6c-13.1,7.8-27.2,13.6-41.9,17.4c-5.2,1.3-9.1,5.6-9.9,10.8l-7.1,42 c-0.2,1.3-1.3,2.2-2.6,2.2h-42.1c-1.3,0-2.4-0.9-2.6-2.2l-7-42c-0.9-5.3-4.8-9.5-9.9-10.8c-14.3-3.7-28.1-9.4-41-16.8 c-2.1-1.2-4.5-1.8-6.8-1.8c-2.7,0-5.5,0.8-7.8,2.5l-35,24.9c-0.5,0.3-1,0.5-1.5,0.5c-0.4,0-1.2-0.1-1.9-0.8l-29.8-29.8 c-0.9-0.9-1-2.3-0.3-3.4l24.6-34.5c3.1-4.4,3.3-10.2,0.6-14.8c-7.8-13-13.8-27.1-17.6-41.8c-1.4-5.1-5.6-9-10.8-9.9l-42.3-7.2 c-1.3-0.2-2.2-1.3-2.2-2.6v-42.1c0-1.3,0.9-2.4,2.2-2.6l41.7-7c5.3-0.9,9.6-4.8,10.9-10c3.7-14.7,9.4-28.9,17.1-42 c2.7-4.6,2.4-10.3-0.7-14.6l-24.9-35c-0.7-1-0.6-2.5,0.3-3.4l29.8-29.8c0.7-0.7,1.4-0.8,1.9-0.8c0.6,0,1.1,0.2,1.5,0.5l34.5,24.6 c4.4,3.1,10.2,3.3,14.8,0.6c13-7.8,27.1-13.8,41.8-17.6c5.1-1.4,9-5.6,9.9-10.8l7.2-42.3c0.2-1.3,1.3-2.2,2.6-2.2h42.1 c1.3,0,2.4,0.9,2.6,2.2l7,41.7c0.9,5.3,4.8,9.6,10,10.9c15.1,3.8,29.5,9.7,42.9,17.6c4.6,2.7,10.3,2.5,14.7-0.6l34.5-24.8 c0.5-0.3,1-0.5,1.5-0.5c0.4,0,1.2-0.1,1.9-0.8l29.8,29.8c0.9,0.9,1,2.3,0.3,3.4l-24.7,34.7c-3.1,4.3-3.3,10.1-0.6,14.7 c7.8,13.1,13.6,27.2,17.4,41.9c1.3,5.2,5.6,9.1,10.8,9.9l42,7.1c1.3,0.2,2.2,1.3,2.2,2.6v42.1H451.9z"/> <path d="M239.4,136.001c-57,0-103.3,46.3-103.3,103.3s46.3,103.3,103.3,103.3s103.3-46.3,103.3-103.3S296.4,136.001,239.4,136.001 z M239.4,315.601c-42.1,0-76.3-34.2-76.3-76.3s34.2-76.3,76.3-76.3s76.3,34.2,76.3,76.3S281.5,315.601,239.4,315.601z"/> </g> </g>
    </svg>
  );
}

function LogoutIcon() {
  return (
    <svg fill="#ffffff" viewBox="0 0 24 24" width={30} height={30} xmlns="http://www.w3.org/2000/svg">
      <path d="M7.707,8.707,5.414,11H17a1,1,0,0,1,0,2H5.414l2.293,2.293a1,1,0,1,1-1.414,1.414l-4-4a1,1,0,0,1,0-1.414l4-4A1,1,0,0,1,7.707,8.707ZM21,1H13a1,1,0,0,0,0,2h7V21H13a1,1,0,0,0,0,2h8a1,1,0,0,0,1-1V2A1,1,0,0,0,21,1Z"/>
    </svg>
  );
}

export default function Sidebar({ onCalendarClick, onEmployeeDirectoryClick }) {
  const [showAssetRequestModal, setShowAssetRequestModal] = useState(false);

  const handleAssetRequest = async (requestText) => {
    try {
      const response = await fetch('/api/asset-requests', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ request_text: requestText })
      });
      
      if (response.ok) {
        alert('Asset request submitted successfully! IT admin has been notified.');
      } else {
        throw new Error('Failed to submit request');
      }
    } catch (error) {
      console.error('Failed to submit asset request:', error);
      alert('Failed to submit request. Please try again.');
      throw error;
    }
  };

  return (
    <>
    <div className="bg-[#115948] text-white w-24 h-full flex flex-col items-center py-6 space-y-28 rounded-r-3xl">
      <div className="w-14 h-14 flex items-center justify-center font-bold text-3xl mb-8">
        <img src={finLogo} alt="Fin Logo" className="h-12 w-12 object-contain" />
      </div>
      <div className="flex flex-col gap-16 items-center flex-1">
        <button className="w-16 h-16 bg-[#115948] rounded-full flex items-center justify-center hover:bg-[#177761] transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95"
          onClick={onCalendarClick}
        >
          <CalendarIcon />
        </button>
        <button className="w-16 h-16 bg-[#115948] rounded-full flex items-center justify-center hover:bg-[#177761] transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95"
          onClick={onEmployeeDirectoryClick}
        >
          <PeopleIcon />
        </button>
          <button 
            className="w-16 h-16 bg-[#115948] rounded-full flex items-center justify-center hover:bg-[#177761] transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95"
            onClick={() => setShowAssetRequestModal(true)}
          >
          <SettingsIcon />
        </button>
        <button className="w-16 h-16 bg-[#177761] rounded-full flex items-center justify-center hover:bg-[#115948] mt-auto transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95"
          onClick={async () => {
            await fetch('/api/auth/logout', {
              method: 'GET',
              credentials: 'include',
            });
            window.location.href = '/'; // or '/login' if you want to go directly to login
          }}
        >
          <LogoutIcon />
        </button>
      </div>
    </div>

      {/* Asset Request Modal */}
      {showAssetRequestModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl p-6 w-full max-w-md">
            <h3 className="text-xl font-bold text-gray-800 mb-4">Request Asset / IT Support</h3>
            <form onSubmit={async (e) => {
              e.preventDefault();
              const formData = new FormData(e.target);
              const requestText = formData.get('request_text');
              if (!requestText.trim()) return;
              
              try {
                await handleAssetRequest(requestText);
                setShowAssetRequestModal(false);
                e.target.reset();
              } catch (error) {
                // Error handled in handleAssetRequest
              }
            }}>
              <textarea
                name="request_text"
                placeholder="Describe your asset request or IT issue..."
                className="w-full h-32 p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-[#115948] focus:border-transparent"
                required
              />
              <div className="flex gap-3 mt-4">
                <button
                  type="button"
                  onClick={() => setShowAssetRequestModal(false)}
                  className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 px-4 py-2 bg-[#115948] text-white rounded-lg hover:bg-[#0e4a3c]"
                >
                  Submit Request
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
} 