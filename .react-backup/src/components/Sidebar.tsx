import { NavLink, useNavigate } from 'react-router-dom'
import { 
  LayoutDashboard, 
  Truck, 
  MapPin, 
  Users, 
  X,
  LogOut
} from 'lucide-react'
import { cn } from '../lib/utils'
import logoImg from '../assets/Logo1.png'

interface SidebarProps {
  isOpen: boolean
  setIsOpen: (isOpen: boolean) => void
}

export default function Sidebar({ isOpen, setIsOpen }: SidebarProps) {
  const navigate = useNavigate()

  const handleSignOut = () => {
    localStorage.removeItem('tms_authenticated')
    navigate('/login')
  }
  const menuItems = [
    { name: 'Dashboard', path: '/app', icon: LayoutDashboard },
    { name: 'Shipments', path: '/app/shipments', icon: Truck },
    { name: 'Tracking View', path: '/app/tracking', icon: MapPin },
    { name: 'Drivers & Fleet', path: '/app/drivers', icon: Users },
  ]

  return (
    <>
      {/* Mobile Sidebar Backdrop */}
      {isOpen && (
        <div 
          className="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm lg:hidden"
          onClick={() => setIsOpen(false)}
        />
      )}

      {/* Sidebar Container */}
      <aside 
        className={cn(
          "fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-[#0f172a] text-slate-200 border-r border-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:h-screen",
          isOpen ? "translate-x-0" : "-translate-x-full"
        )}
      >
        {/* Sidebar Header */}
        <div className="flex h-16 items-center justify-between px-6 border-b border-slate-850">
          <div className="flex items-center gap-2.5">
            <img src={logoImg} className="h-9 w-9 rounded-lg object-contain bg-slate-800/40 p-0.5 border border-slate-700" alt="FLEET logo" />
            <span className="text-lg font-bold tracking-wider text-white">FLEET</span>
          </div>
          <button 
            onClick={() => setIsOpen(false)}
            className="rounded-lg p-1.5 hover:bg-slate-800 lg:hidden text-slate-400 hover:text-slate-200"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Sidebar Navigation */}
        <nav className="flex-1 space-y-1.5 px-4 py-6 overflow-y-auto">
          {menuItems.map((item) => {
            const Icon = item.icon
            return (
              <NavLink
                key={item.path}
                to={item.path}
                onClick={() => setIsOpen(false)}
                className={({ isActive }) => cn(
                  "flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 group",
                  isActive 
                    ? "bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/10" 
                    : "text-slate-450 hover:bg-slate-850 hover:text-slate-100"
                )}
              >
                {({ isActive }) => (
                  <>
                    <Icon className={cn(
                      "h-5 w-5 transition-transform duration-200 group-hover:scale-105",
                      isActive ? "text-white" : "text-slate-400 group-hover:text-slate-200"
                    )} />
                    {item.name}
                  </>
                )}
              </NavLink>
            )
          })}
        </nav>

        {/* Sidebar Footer */}
        <div className="p-4 border-t border-slate-850 bg-slate-900/40 flex items-center justify-between">
          <div className="flex items-center gap-3 px-2">
            <div className="h-9 w-9 rounded-full bg-slate-700 overflow-hidden flex items-center justify-center border border-slate-800 text-white font-semibold shrink-0">
              JD
            </div>
            <div>
              <p className="text-xs font-semibold text-white">John Doe</p>
              <p className="text-[10px] text-slate-400">Fleet Dispatcher</p>
            </div>
          </div>
          <button
            onClick={handleSignOut}
            className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-850 hover:text-white transition-colors cursor-pointer"
            title="Sign Out"
          >
            <LogOut className="h-4.5 w-4.5" />
          </button>
        </div>
      </aside>
    </>
  )
}
