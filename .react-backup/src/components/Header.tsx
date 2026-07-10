import { Menu, Bell, Search, Sun, Moon } from 'lucide-react'

interface HeaderProps {
  onMenuClick: () => void
  title: string
  theme: string
  toggleTheme: () => void
}

export default function Header({ onMenuClick, title, theme, toggleTheme }: HeaderProps) {
  return (
    <header className="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md px-6 shadow-sm transition-colors duration-300">
      <div className="flex items-center gap-4">
        {/* Toggle Mobile Menu Button */}
        <button
          type="button"
          onClick={onMenuClick}
          className="rounded-lg p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white lg:hidden transition-colors"
        >
          <Menu className="h-6 w-6" />
        </button>

        {/* Dynamic Title / Breadcrumb */}
        <h1 className="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-100 lg:text-2xl m-0 leading-none transition-colors">
          {title}
        </h1>
      </div>

      {/* Utilities */}
      <div className="flex items-center gap-3">
        {/* Quick Search Panel */}
        <div className="relative hidden max-w-xs sm:block">
          <Search className="absolute top-1/2 left-3 h-4.5 w-4.5 -translate-y-1/2 text-slate-450 dark:text-slate-500" />
          <input
            type="text"
            placeholder="Quick search..."
            className="w-44 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950 py-1.5 pr-4 pl-9 text-xs text-slate-800 dark:text-slate-200 transition-all focus:w-56 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
          />
        </div>

        {/* System Status indicator */}
        <div className="hidden items-center gap-2 rounded-full border border-green-200 dark:border-green-950/30 bg-green-50 dark:bg-green-950/10 px-3 py-1 text-xs text-green-750 dark:text-green-450 md:flex transition-colors">
          <span className="relative flex h-2 w-2">
            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
          </span>
          System Online
        </div>

        {/* Dark Mode Toggle */}
        <button
          type="button"
          onClick={toggleTheme}
          className="relative rounded-full p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors cursor-pointer"
          title={theme === 'dark' ? 'Activate Light Mode' : 'Activate Dark Mode'}
        >
          {theme === 'dark' ? (
            <Sun className="h-5 w-5 text-amber-500 transition-transform duration-300 hover:rotate-45" />
          ) : (
            <Moon className="h-5 w-5 transition-transform duration-300 hover:-rotate-12" />
          )}
        </button>

        {/* Notifications */}
        <button
          type="button"
          className="relative rounded-full p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors"
        >
          <Bell className="h-5 w-5" />
          <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500"></span>
        </button>
      </div>
    </header>
  )
}
