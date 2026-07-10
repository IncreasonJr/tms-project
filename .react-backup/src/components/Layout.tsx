import { useState, useEffect } from 'react'
import { Outlet, useLocation, Navigate } from 'react-router-dom'
import Sidebar from './Sidebar'
import Header from './Header'

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const location = useLocation()
  
  const isAuthenticated = localStorage.getItem('tms_authenticated') === 'true'

  const [theme, setTheme] = useState(() => {
    const saved = localStorage.getItem('theme')
    if (saved) return saved
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  })

  useEffect(() => {
    const root = window.document.documentElement
    if (theme === 'dark') {
      root.classList.add('dark')
    } else {
      root.classList.remove('dark')
    }
    localStorage.setItem('theme', theme)
  }, [theme])

  const toggleTheme = () => {
    setTheme(prev => prev === 'dark' ? 'light' : 'dark')
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  // Dynamically set title based on the active path
  const getPageTitle = (pathname: string) => {
    switch (pathname) {
      case '/app':
        return 'Operations Dashboard'
      case '/app/shipments':
        return 'Shipments Directory'
      case '/app/tracking':
        return 'Fleet Tracking Center'
      case '/app/drivers':
        return 'Drivers & Fleet Vehicles'
      default:
        return 'Transport Management System'
    }
  }

  return (
    <div className="flex h-screen w-screen overflow-hidden bg-slate-100/50 dark:bg-slate-950/20 relative transition-colors duration-300">
      {/* Ambient background glow for glassmorphism */}
      <div className="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full filter blur-[120px] pointer-events-none"></div>
      <div className="absolute bottom-[5%] right-[5%] w-[600px] h-[600px] bg-emerald-500/5 dark:bg-emerald-500/10 rounded-full filter blur-[120px] pointer-events-none"></div>

      {/* Sidebar Navigation */}
      <Sidebar isOpen={sidebarOpen} setIsOpen={setSidebarOpen} />

      {/* Main Content Area */}
      <div className="flex flex-1 flex-col overflow-hidden relative z-10">
        {/* Top Header */}
        <Header 
          onMenuClick={() => setSidebarOpen(true)} 
          title={getPageTitle(location.pathname)} 
          theme={theme}
          toggleTheme={toggleTheme}
        />

        {/* Scrollable View Content */}
        <main className="flex-1 overflow-y-auto px-6 py-8">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
