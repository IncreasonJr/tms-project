import { useState, useEffect } from 'react'
import { 
  Search, 
  Phone, 
  Truck, 
  CheckCircle2, 
  Moon,
  Info,
  ShieldAlert,
  SlidersHorizontal,
  Mail
} from 'lucide-react'
import { mockDrivers } from '../data/mockData'
import type { Driver } from '../types/tms'
import { Skeleton } from '../components/ui/skeleton'

export default function Drivers() {
  const [isLoading, setIsLoading] = useState(true)
  const [drivers] = useState<Driver[]>(mockDrivers)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('All')
  const [vehicleFilter, setVehicleFilter] = useState('All')

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 700)
    return () => clearTimeout(timer)
  }, [])

  // Calculate fleet summaries
  const activeCount = drivers.filter(d => d.status === 'Active').length
  const offDutyCount = drivers.filter(d => d.status === 'Off Duty').length
  const maintenanceCount = drivers.filter(d => d.status === 'Maintenance').length

  // Filter implementation
  const filteredDrivers = drivers.filter((driver) => {
    const matchesSearch = 
      driver.name.toLowerCase().includes(search.toLowerCase()) ||
      driver.vehicleName.toLowerCase().includes(search.toLowerCase()) ||
      driver.vehicleId.toLowerCase().includes(search.toLowerCase()) ||
      driver.id.toLowerCase().includes(search.toLowerCase())

    const matchesStatus = statusFilter === 'All' || driver.status === statusFilter
    const matchesVehicle = vehicleFilter === 'All' || driver.vehicleType === vehicleFilter

    return matchesSearch && matchesStatus && matchesVehicle
  })

  if (isLoading) {
    return (
      <div className="space-y-6">
        {/* Summary Row Skeletons */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <Skeleton className="h-4 w-24 mb-2" />
              <Skeleton className="h-8 w-16" />
            </div>
          ))}
        </div>

        {/* Search Skeletons */}
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <Skeleton className="h-10 w-full" />
        </div>

        {/* Card Grid Skeletons */}
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="flex items-center gap-4">
                <Skeleton className="h-14 w-14 rounded-full" />
                <div className="space-y-1.5 flex-1">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-3 w-16" />
                </div>
              </div>
              <div className="mt-6 space-y-3">
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-full" />
              </div>
            </div>
          ))}
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Fleet Summary KPI Row */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {/* Active Drivers summary */}
        <div className="flex items-center gap-4 bg-white/70 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl p-5 shadow-sm backdrop-blur-md hover:shadow-md hover:scale-[1.02] hover:-translate-y-0.5 transition-all duration-300 animate-in fade-in slide-in-from-bottom-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 shrink-0">
            <CheckCircle2 className="h-6 w-6" />
          </div>
          <div>
            <span className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Operators</span>
            <span className="text-2xl font-bold text-slate-900 dark:text-white mt-0.5 inline-block">{activeCount} <span className="text-xs text-slate-400 dark:text-slate-500 font-semibold">on duty</span></span>
          </div>
        </div>

        {/* Off Duty Summary */}
        <div className="flex items-center gap-4 bg-white/70 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl p-5 shadow-sm backdrop-blur-md hover:shadow-md hover:scale-[1.02] hover:-translate-y-0.5 transition-all duration-300 animate-in fade-in slide-in-from-bottom-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-655 dark:text-slate-400 shrink-0">
            <Moon className="h-6 w-6" />
          </div>
          <div>
            <span className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Off Duty</span>
            <span className="text-2xl font-bold text-slate-900 dark:text-white mt-0.5 inline-block">{offDutyCount} <span className="text-xs text-slate-400 dark:text-slate-500 font-semibold">resting</span></span>
          </div>
        </div>

        {/* Fleet Maintenance Summary */}
        <div className="flex items-center gap-4 bg-white/70 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl p-5 shadow-sm backdrop-blur-md hover:shadow-md hover:scale-[1.02] hover:-translate-y-0.5 transition-all duration-300 animate-in fade-in slide-in-from-bottom-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 shrink-0">
            <ShieldAlert className="h-6 w-6" />
          </div>
          <div>
            <span className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">In Maintenance</span>
            <span className="text-2xl font-bold text-slate-900 dark:text-white mt-0.5 inline-block">{maintenanceCount} <span className="text-xs text-slate-400 dark:text-slate-500 font-semibold">vehicles</span></span>
          </div>
        </div>
      </div>

      {/* Grid Filters Panel */}
      <div className="flex flex-col gap-4 md:flex-row md:items-center justify-between bg-white/70 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl p-4 shadow-sm backdrop-blur-md hover:shadow-md transition-all duration-300 animate-in fade-in slide-in-from-bottom-4">
        <div className="relative flex-1">
          <Search className="absolute top-1/2 left-3 h-4.5 w-4.5 -translate-y-1/2 text-slate-400 dark:text-slate-550" />
          <input
            type="text"
            placeholder="Search by driver name, ID, truck name..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 py-2 pr-4 pl-10 text-sm text-slate-800 dark:text-slate-205 transition-all focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
          />
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <div className="flex items-center gap-2">
            <SlidersHorizontal className="h-4 w-4 text-slate-400" />
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-205 focus:border-blue-500 focus:outline-none"
            >
              <option value="All">All Statuses</option>
              <option value="Active">Active</option>
              <option value="Off Duty">Off Duty</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>

          <select
            value={vehicleFilter}
            onChange={(e) => setVehicleFilter(e.target.value)}
            className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-205 focus:border-blue-500 focus:outline-none"
          >
            <option value="All">All Vehicle Types</option>
            <option value="Semi-Truck">Semi-Trucks</option>
            <option value="Box Truck">Box Trucks</option>
            <option value="Cargo Van">Cargo Vans</option>
          </select>
        </div>
      </div>

      {/* Grid of Drivers Cards */}
      {filteredDrivers.length > 0 ? (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {filteredDrivers.map((driver) => {
            // Status styling helper
            let statusBadge = 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
            let statusDot = 'bg-slate-400'
            if (driver.status === 'Active') {
              statusBadge = 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-450 border-emerald-200 dark:border-emerald-900/30'
              statusDot = 'bg-emerald-500'
            } else if (driver.status === 'Maintenance') {
              statusBadge = 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-455 border-rose-200 dark:border-rose-900/30'
              statusDot = 'bg-rose-500'
            }

            return (
              <div 
                key={driver.id} 
                className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/75 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-lg hover:scale-[1.04] hover:-translate-y-1.5 hover:border-blue-500/25 transition-all duration-300 ease-out flex flex-col justify-between animate-in fade-in slide-in-from-bottom-4"
              >
                {/* Header Profile Section */}
                <div className="flex items-center gap-4">
                  <img
                    src={driver.avatar}
                    alt={driver.name}
                    className="h-14 w-14 rounded-full object-cover bg-white/40 border border-white/30 shadow-inner"
                  />
                  <div>
                    <h3 className="font-bold text-slate-900 dark:text-slate-50 text-base leading-snug">{driver.name}</h3>
                    <p className="text-xs text-slate-455 dark:text-slate-400 mt-0.5 font-semibold">Operator ID: {driver.id}</p>
                  </div>
                </div>

                {/* Status Indicator Bar */}
                <div className="mt-4 flex items-center justify-between border-y border-white/20 dark:border-slate-800 py-2.5 my-4 bg-white/25 dark:bg-slate-950/30 px-2.5 rounded-lg">
                  <span className="text-xs text-slate-500 dark:text-slate-400 font-semibold">Duty Status</span>
                  <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold leading-none ${statusBadge}`}>
                    <span className={`h-1.5 w-1.5 rounded-full ${statusDot}`}></span>
                    {driver.status}
                  </span>
                </div>

                {/* Fleet assignment Info */}
                <div className="space-y-2.5">
                  <div className="flex items-center gap-2.5 text-xs text-slate-655 dark:text-slate-400 font-medium">
                    <Truck className="h-4.5 w-4.5 text-slate-450 dark:text-slate-500 shrink-0" />
                    <div>
                      <p className="text-slate-800 dark:text-slate-205 font-semibold">{driver.vehicleName}</p>
                      <p className="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{driver.vehicleType} • ID: {driver.vehicleId}</p>
                    </div>
                  </div>
                  
                  <div className="flex items-center gap-2.5 text-xs text-slate-655 dark:text-slate-400 font-medium">
                    <Phone className="h-4.5 w-4.5 text-slate-450 dark:text-slate-500 shrink-0" />
                    <span className="font-mono text-slate-700 dark:text-slate-350">{driver.contact}</span>
                  </div>
                </div>

                {/* Action buttons */}
                <div className="mt-6 pt-4 border-t border-white/20 dark:border-slate-850 flex items-center gap-2">
                  <a
                    href={`tel:${driver.contact}`}
                    className="flex-1 flex items-center justify-center gap-1.5 rounded-xl border border-white/35 dark:border-white/10 bg-white/45 dark:bg-slate-800/40 backdrop-blur-xs py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-white/70 dark:hover:bg-slate-850 hover:border-white/55 dark:hover:border-slate-700 hover:text-slate-900 dark:hover:text-white transition-all duration-200 cursor-pointer"
                  >
                    <Phone className="h-3.5 w-3.5" />
                    Call Operator
                  </a>
                  <button
                    className="rounded-xl border border-white/35 dark:border-white/10 bg-white/45 dark:bg-slate-800/40 backdrop-blur-xs p-2 text-slate-600 dark:text-slate-350 hover:bg-white/70 dark:hover:bg-slate-850 hover:border-white/55 dark:hover:border-slate-700 hover:text-slate-900 dark:hover:text-white transition-all duration-200 cursor-pointer"
                    title="Send Message"
                  >
                    <Mail className="h-4 w-4" />
                  </button>
                </div>
              </div>
            )
          })}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 p-8 text-center shadow-sm">
          <Info className="h-10 w-10 text-slate-350 dark:text-slate-550 mb-2" />
          <p className="text-slate-500 dark:text-slate-400 font-semibold text-sm">No drivers match your current filter settings.</p>
        </div>
      )}
    </div>
  )
}
