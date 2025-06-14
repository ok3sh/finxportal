import { useEffect, useState } from 'react';
import AgendaCalendar from './AgendaCalendar';
import SharedCalendar from './SharedCalendar';

export default function Dashboard() {
  const [user, setUser] = useState(null);
  const [links, setLinks] = useState([]);
  const [linksLoading, setLinksLoading] = useState(true);
  const [currentSlide, setCurrentSlide] = useState(0);
  const [dashboardError, setDashboardError] = useState(null);

  const linksPerSlide = 4; // Show 4 links per slide
  const totalSlides = Math.ceil(links.length / linksPerSlide);

  useEffect(() => {
    console.log('🏠 Dashboard: Component mounted');
    
    // Fetch user authentication status
    console.log('🔄 Dashboard: Fetching user status...');
    fetch('/api/auth/status', { credentials: 'include' })
      .then(res => {
        console.log('🔄 Dashboard: User status response:', res.status);
        return res.json();
      })
      .then(data => {
        console.log('👤 Dashboard: User data:', data);
        if (data.isAuthenticated && data.user && data.user.profile) {
          setUser(data.user.profile);
        }
      })
      .catch(error => {
        console.error('❌ Dashboard: Failed to fetch user:', error);
        setDashboardError('Failed to load user information');
      });

    // Fetch personalized links from the link repository
    console.log('🔄 Dashboard: Fetching links...');
    fetch('/api/links', { credentials: 'include' })
      .then(res => {
        console.log('🔄 Dashboard: Links response:', res.status);
        return res.json();
      })
      .then(data => {
        console.log('🔗 Dashboard: Links data:', data);
        setLinks(Array.isArray(data) ? data : []);
        setLinksLoading(false);
      })
      .catch(error => {
        console.error('❌ Dashboard: Failed to fetch links:', error);
        // Fallback to default links if API fails
        setLinks([
          {
            id: 1,
            name: 'Keka',
            url: 'https://keka.com',
            logo: '/assets/keka.png',
            background_color: '#115948',
            sort_order: 1,
            is_personalized: false
          },
          {
            id: 2,
            name: 'Zoho',
            url: 'https://zoho.com',
            logo: '/assets/zoho.png',
            background_color: '#115948',
            sort_order: 2,
            is_personalized: false
          }
        ]);
        setLinksLoading(false);
        setDashboardError('Using fallback links due to API error');
      });
  }, []);

  const goToPrevSlide = () => {
    setCurrentSlide(prev => (prev === 0 ? totalSlides - 1 : prev - 1));
  };

  const goToNextSlide = () => {
    setCurrentSlide(prev => (prev === totalSlides - 1 ? 0 : prev + 1));
  };

  const getCurrentSlideLinks = () => {
    const startIndex = currentSlide * linksPerSlide;
    const endIndex = startIndex + linksPerSlide;
    return links.slice(startIndex, endIndex);
  };

  console.log('🏠 Dashboard: Rendering with:', { 
    user: user?.displayName, 
    linksCount: links.length, 
    linksLoading, 
    dashboardError 
  });

  // Show error state if there's a critical error
  if (dashboardError && links.length === 0 && !linksLoading) {
    return (
      <div className="flex flex-col gap-4 w-full h-full px-8 py-6">
        <div className="flex items-center justify-center flex-grow">
          <div className="text-center">
            <div className="text-red-500 text-xl mb-4">⚠️ Dashboard Error</div>
            <p className="text-gray-600 mb-4">{dashboardError}</p>
            <button 
              onClick={() => window.location.reload()} 
              className="bg-[#115948] text-white px-4 py-2 rounded-lg hover:bg-[#0f4a3e]"
            >
              Reload Page
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4 w-full h-full px-8 py-6">
      <h1 className="text-3xl font-bold mb-2">
        Welcome back{user ? `, ${user.displayName}` : ', employee'}
      </h1>
      
      {/* Show dashboard error as a warning banner */}
      {dashboardError && (
        <div className="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 mb-4 rounded">
          <p className="text-sm">{dashboardError}</p>
        </div>
      )}
      
      {/* Dynamic Links Slider Section */}
      <div className="relative">
        {linksLoading ? (
          <div className="flex items-center justify-center py-16">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#115948] mr-3"></div>
            <span className="text-gray-500">Loading links...</span>
          </div>
        ) : (
          <>
            {/* Links Container */}
            <div className="flex gap-8 mb-4 overflow-hidden">
              {getCurrentSlideLinks().map((link) => (
                <a
                  key={link.id}
                  href={link.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="bg-[#115948] rounded-3xl flex items-center justify-center transition duration-200 hover:scale-105 hover:shadow-lg active:scale-95 group text-white text-decoration-none flex-shrink-0"
                  style={{ 
                    width: '210px', 
                    height: '144px',
                    backgroundColor: link.background_color || '#115948'
                  }}
                  title={link.is_personalized ? `${link.name} (Personalized for your group)` : link.name}
                >
                  {link.logo ? (
                    <img 
                      src={link.logo} 
                      alt={link.name} 
                      className="h-16 object-contain transition duration-200 group-hover:scale-110" 
                    />
                  ) : (
                    <span 
                      className={`text-lg font-semibold transition duration-200 group-hover:scale-110 text-center px-4 ${link.is_personalized ? 'font-bold' : ''}`}
                    >
                      {link.name}
                    </span>
                  )}
                </a>
              ))}
            </div>

            {/* Navigation Handles */}
            {totalSlides > 1 && (
              <>
                {/* Left Handle */}
                <button
                  onClick={goToPrevSlide}
                  className="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 bg-[#115948] hover:bg-[#0f4a3e] text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg transition-all duration-200 hover:scale-110 active:scale-95 z-10"
                  aria-label="Previous links"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                  </svg>
                </button>

                {/* Right Handle */}
                <button
                  onClick={goToNextSlide}
                  className="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 bg-[#115948] hover:bg-[#0f4a3e] text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg transition-all duration-200 hover:scale-110 active:scale-95 z-10"
                  aria-label="Next links"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </button>

                {/* Slide Indicators */}
                <div className="flex justify-center mt-4 space-x-2">
                  {Array.from({ length: totalSlides }, (_, index) => (
                    <button
                      key={index}
                      onClick={() => setCurrentSlide(index)}
                      className={`w-3 h-3 rounded-full transition-all duration-200 ${
                        index === currentSlide 
                          ? 'bg-[#115948] scale-125' 
                          : 'bg-gray-300 hover:bg-gray-400'
                      }`}
                      aria-label={`Go to slide ${index + 1}`}
                    />
                  ))}
                </div>
              </>
            )}
          </>
        )}
      </div>

      {/* Two-column calendar layout */}
      <div className="flex gap-6 flex-grow" style={{ maxHeight: '444px' }}>
        {/* Main Calendar (60% width) */}
        <div className="flex-[0_0_60%] bg-[#115948] rounded-3xl p-4 overflow-auto">
          <AgendaCalendar />
        </div>

        {/* Shared Calendar (35% width) */}
        <div className="flex-[0_0_35%] bg-[#115948] rounded-3xl p-4 overflow-auto">
          <SharedCalendar />
        </div>
      </div>
    </div>
  );
} 