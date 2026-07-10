import { useState, useEffect } from 'react'
import { 
  TrendingUp, 
  Truck, 
  Clock, 
  Users, 
  DollarSign, 
  ArrowRight,
  AlertTriangle,
  CheckCircle,
  PlusCircle,
  ShieldCheck
} from 'lucide-react'
import { mockMetrics, mockRecentActivity, mockShipments } from '../data/mockData'
import { Skeleton } from '../components/ui/skeleton'

export default function Dashboard() {
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 800)
    return () => clearTimeout(timer)
  }, [])

  // Calculate shipment status distribution
  const statusCounts = mockShipments.reduce((acc, curr) => {
    acc[curr.status] = (acc[curr.status] || 0) + 1
    return acc
  }, {} as Record<string, number>)

  const total = mockShipments.length
  const statusPercentages = {
    inTransit: Math.round(((statusCounts['In Transit'] || 0) / total) * 100),
    delivered: Math.round(((statusCounts['Delivered'] || 0) / total) * 100),
    pending: Math.round(((statusCounts['Pending'] || 0) / total) * 100),
    delayed: Math.round(((statusCounts['Delayed'] || 0) / total) * 100)
  }

  // Format currency
  const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS',
      maximumFractionDigits: 0
    }).format(val)
  }

  if (isLoading) {
    return (
      <div className="space-y-6">
        {/* KPI Grid */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="flex items-center justify-between">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-8 w-8 rounded-lg" />
              </div>
              <Skeleton className="mt-4 h-8 w-16" />
              <div className="mt-4 flex items-center gap-1.5">
                <Skeleton className="h-4 w-32" />
              </div>
            </div>
          ))}
        </div>

        {/* Charts and Activity Grid */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <Skeleton className="h-6 w-48 mb-6" />
            <Skeleton className="h-64 w-full" />
          </div>
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <Skeleton className="h-6 w-36 mb-6" />
            <div className="space-y-4">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="flex gap-3">
                  <Skeleton className="h-8 w-8 rounded-full" />
                  <div className="flex-1 space-y-1.5">
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-3 w-16" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* KPI Cards Grid */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {/* Total Shipments */}
        <div className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/75 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-lg hover:scale-[1.03] hover:-translate-y-1 hover:border-blue-500/25 transition-all duration-300 ease-out animate-in fade-in slide-in-from-bottom-4">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Shipments</span>
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
              <Truck className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-extrabold text-slate-900 dark:text-slate-50">{mockMetrics.totalShipments}</span>
            <span className="flex items-center gap-0.5 text-xs font-semibold text-green-600">
              <TrendingUp className="h-3.5 w-3.5" />
              +{mockMetrics.shipmentChange}%
            </span>
          </div>
          <p className="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Active dispatch cycles</p>
        </div>

        {/* On-Time Delivery Rate */}
        <div className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/75 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-lg hover:scale-[1.03] hover:-translate-y-1 hover:border-emerald-500/25 transition-all duration-300 ease-out animate-in fade-in slide-in-from-bottom-4">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">On-Time Rate</span>
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
              <Clock className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-extrabold text-slate-900 dark:text-slate-50">{mockMetrics.onTimeDeliveryRate}%</span>
            <span className="flex items-center gap-0.5 text-xs font-semibold text-emerald-600">
              <ShieldCheck className="h-3.5 w-3.5" />
              Optimal
            </span>
          </div>
          <p className="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Target rate threshold 95.0%</p>
        </div>

        {/* Active Drivers */}
        <div className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/75 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-lg hover:scale-[1.03] hover:-translate-y-1 hover:border-amber-500/25 transition-all duration-300 ease-out animate-in fade-in slide-in-from-bottom-4">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active Operators</span>
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
              <Users className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-extrabold text-slate-900 dark:text-slate-50">{mockMetrics.activeDrivers}</span>
            <span className="text-xs text-slate-500 dark:text-slate-450">/ 48 vehicles</span>
          </div>
          <p className="mt-1.5 text-xs text-slate-500 dark:text-slate-400">92% fleet utilization rate</p>
        </div>

        {/* Total Revenue */}
        <div className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/75 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-lg hover:scale-[1.03] hover:-translate-y-1 hover:border-purple-500/25 transition-all duration-300 ease-out animate-in fade-in slide-in-from-bottom-4">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Gross Revenue</span>
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400">
              <DollarSign className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-extrabold text-slate-900 dark:text-slate-50">{formatCurrency(mockMetrics.totalRevenue)}</span>
            <span className="flex items-center gap-0.5 text-xs font-semibold text-green-600">
              <TrendingUp className="h-3.5 w-3.5" />
              +{mockMetrics.revenueChange}%
            </span>
          </div>
          <p className="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Month-to-date invoicing</p>
        </div>
      </div>

      {/* Analytics & Activity Row */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* SVG/CSS Shipment Status Chart */}
        <div className="lg:col-span-2 rounded-2xl border border-white/40 dark:border-white/10 bg-white/70 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-300 ease-out animate-in fade-in slide-in-from-bottom-4 flex flex-col justify-between">
          <div>
            <h3 className="text-base font-bold text-slate-900 dark:text-slate-50">Shipment Status Breakdown</h3>
            <p className="text-xs text-slate-550 dark:text-slate-400 mb-6">Distribution and volume metrics for active cycles</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-center py-4">
            {/* SVG Donut */}
            <div className="relative flex justify-center">
              <svg className="w-48 h-48 transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="40" className="stroke-slate-100 dark:stroke-slate-800 fill-none" strokeWidth="12" />
                
                {/* Delivered Segment (e.g. 2/8 -> 25%) */}
                <circle 
                  cx="50" cy="50" r="40" 
                  className="stroke-emerald-555 fill-none transition-all duration-1000" 
                  strokeWidth="12" 
                  strokeDasharray="251.2"
                  strokeDashoffset={251.2 - (251.2 * statusPercentages.delivered) / 100}
                />

                {/* In Transit Segment (e.g. 3/8 -> 37.5%) */}
                <circle 
                  cx="50" cy="50" r="40" 
                  className="stroke-blue-500 fill-none transition-all duration-1000" 
                  strokeWidth="12" 
                  strokeDasharray="251.2"
                  strokeDashoffset={251.2 - (251.2 * statusPercentages.inTransit) / 100}
                  transform={`rotate(${(statusPercentages.delivered / 100) * 360} 50 50)`}
                />

                {/* Pending Segment (e.g. 2/8 -> 25%) */}
                <circle 
                  cx="50" cy="50" r="40" 
                  className="stroke-slate-400 fill-none transition-all duration-1000" 
                  strokeWidth="12" 
                  strokeDasharray="251.2"
                  strokeDashoffset={251.2 - (251.2 * statusPercentages.pending) / 100}
                  transform={`rotate(${((statusPercentages.delivered + statusPercentages.inTransit) / 100) * 360} 50 50)`}
                />

                {/* Delayed Segment (e.g. 1/8 -> 12.5%) */}
                <circle 
                  cx="50" cy="50" r="40" 
                  className="stroke-rose-500 fill-none transition-all duration-1000" 
                  strokeWidth="12" 
                  strokeDasharray="251.2"
                  strokeDashoffset={251.2 - (251.2 * statusPercentages.delayed) / 100}
                  transform={`rotate(${((statusPercentages.delivered + statusPercentages.inTransit + statusPercentages.pending) / 100) * 360} 50 50)`}
                />
              </svg>
              {/* Text overlay */}
              <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="text-2xl font-black text-slate-800 dark:text-slate-100">{total}</span>
                <span className="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Shipments</span>
              </div>
            </div>

            {/* Labels and progress lines */}
            <div className="space-y-4">
              {/* Delivered */}
              <div>
                <div className="flex items-center justify-between text-xs font-semibold mb-1">
                  <div className="flex items-center gap-2">
                    <span className="h-3 w-3 rounded-full bg-emerald-500"></span>
                    <span className="text-slate-700 dark:text-slate-355">Delivered</span>
                  </div>
                  <span className="text-slate-900 dark:text-slate-100">{statusCounts['Delivered'] || 0} ({statusPercentages.delivered}%)</span>
                </div>
                <div className="h-1.5 w-full bg-slate-100 dark:bg-slate-850 rounded-full overflow-hidden">
                  <div className="h-full bg-emerald-500 rounded-full" style={{ width: `${statusPercentages.delivered}%` }}></div>
                </div>
              </div>

              {/* In Transit */}
              <div>
                <div className="flex items-center justify-between text-xs font-semibold mb-1">
                  <div className="flex items-center gap-2">
                    <span className="h-3 w-3 rounded-full bg-blue-500"></span>
                    <span className="text-slate-700 dark:text-slate-355">In Transit</span>
                  </div>
                  <span className="text-slate-900 dark:text-slate-100">{statusCounts['In Transit'] || 0} ({statusPercentages.inTransit}%)</span>
                </div>
                <div className="h-1.5 w-full bg-slate-100 dark:bg-slate-850 rounded-full overflow-hidden">
                  <div className="h-full bg-blue-500 rounded-full" style={{ width: `${statusPercentages.inTransit}%` }}></div>
                </div>
              </div>

              {/* Pending */}
              <div>
                <div className="flex items-center justify-between text-xs font-semibold mb-1">
                  <div className="flex items-center gap-2">
                    <span className="h-3 w-3 rounded-full bg-slate-400"></span>
                    <span className="text-slate-700 dark:text-slate-355">Pending Pickup</span>
                  </div>
                  <span className="text-slate-900 dark:text-slate-100">{statusCounts['Pending'] || 0} ({statusPercentages.pending}%)</span>
                </div>
                <div className="h-1.5 w-full bg-slate-100 dark:bg-slate-850 rounded-full overflow-hidden">
                  <div className="h-full bg-slate-400 rounded-full" style={{ width: `${statusPercentages.pending}%` }}></div>
                </div>
              </div>

              {/* Delayed */}
              <div>
                <div className="flex items-center justify-between text-xs font-semibold mb-1">
                  <div className="flex items-center gap-2">
                    <span className="h-3 w-3 rounded-full bg-rose-500"></span>
                    <span className="text-slate-700 dark:text-slate-355">Delayed</span>
                  </div>
                  <span className="text-slate-900 dark:text-slate-100">{statusCounts['Delayed'] || 0} ({statusPercentages.delayed}%)</span>
                </div>
                <div className="h-1.5 w-full bg-slate-100 dark:bg-slate-850 rounded-full overflow-hidden">
                  <div className="h-full bg-rose-500 rounded-full" style={{ width: `${statusPercentages.delayed}%` }}></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Recent Activity Feed */}
        <div className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/70 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-300 ease-out animate-in fade-in slide-in-from-bottom-4 flex flex-col">
          <h3 className="text-base font-bold text-slate-900 dark:text-slate-50 mb-1">Recent Activity</h3>
          <p className="text-xs text-slate-550 dark:text-slate-400 mb-6">Real-time logistics status feed</p>

          <div className="flex-1 space-y-5 overflow-y-auto pr-1">
            {mockRecentActivity.map((activity) => {
              // Icon & color logic based on activity type
              let Icon = PlusCircle
              let iconBg = 'bg-blue-50 text-blue-600'
              if (activity.type === 'delivery') {
                Icon = CheckCircle
                iconBg = 'bg-emerald-50 text-emerald-600'
              } else if (activity.type === 'status_change') {
                Icon = AlertTriangle
                iconBg = 'bg-rose-50 text-rose-600'
              } else if (activity.type === 'driver_assign') {
                Icon = Users
                iconBg = 'bg-amber-50 text-amber-600'
              }

              return (
                <div key={activity.id} className="flex items-start gap-3">
                  <div className={`mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${iconBg}`}>
                    <Icon className="h-4.5 w-4.5" />
                  </div>
                  <div className="flex-1">
                    <p className="text-xs font-medium text-slate-850 dark:text-slate-200 leading-relaxed">
                      {activity.message}
                    </p>
                    <div className="mt-1 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-550">
                      <span>{activity.user}</span>
                      <span>{activity.time}</span>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>

          <button className="mt-5 flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-800 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-450 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
            View Operations Log
            <ArrowRight className="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </div>
  )
}
