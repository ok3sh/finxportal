import { useState } from 'react'
import './Login.css'
import microsoftLogo from '../assets/microsoft-logo.png';

const Login = () => {
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState(null)

  const handleMicrosoftLogin = async () => {
    try {
      setIsLoading(true)
      setError(null)
      const response = await fetch('/auth/login', {
        credentials: 'include'
      })
      const data = await response.json()
      if (data.url) {
        window.location.href = data.url
      } else {
        throw new Error('Login URL not received')
      }
    } catch (err) {
      setError('Failed to initialize login. Please try again.')
      console.error('Login error:', err)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="landing-page">
      <div className="login-container">
        {error && <div className="error-message">{error}</div>}
        <button
          onClick={handleMicrosoftLogin}
          disabled={isLoading}
        >
          {isLoading ? (
            <span className="loading-spinner"></span>
          ) : (
            <>
              <img
                src={microsoftLogo}
                alt="Microsoft"
                className="microsoft-icon"
              />
              Sign in with Microsoft
            </>
          )}
        </button>
      </div>
    </div>
  )
}

export default Login 