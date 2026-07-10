import { useState, useEffect } from 'react'
import { 
  Search, 
  ArrowRight,
  Compass,
  Truck
} from 'lucide-react'
import { mockShipments } from '../data/mockData'
import type { Shipment } from '../types/tms'
import { Skeleton } from '../components/ui/skeleton'

// SVG coordinates for Ghana hubs
const cityCoordinates: Record<string, { x: number; y: number }> = {
  'Accra': { x: 280, y: 250 },
  'Tema': { x: 310, y: 245 },
  'Takoradi': { x: 150, y: 260 },
  'Cape Coast': { x: 210, y: 255 },
  'Kumasi': { x: 200, y: 170 },
  'Sunyani': { x: 140, y: 150 },
  'Tamale': { x: 240, y: 75 },
  'Koforidua': { x: 270, y: 210 },
  'Techiman': { x: 190, y: 125 },
  'Ho': { x: 330, y: 200 }
}

export default function Tracking() {
  const [isLoading, setIsLoading] = useState(true)
  const [shipments, setShipments] = useState<Shipment[]>([])
  const [selectedShipment, setSelectedShipment] = useState<Shipment | null>(null)
  const [search, setSearch] = useState('')

  useEffect(() => {
    // Load from local storage or mock data
    const saved = localStorage.getItem('tms_shipments')
    const loadedShipments = saved ? JSON.parse(saved) : mockShipments
    setShipments(loadedShipments)
    
    // Default to the first In Transit shipment
    const active = loadedShipments.find((s: Shipment) => s.status === 'In Transit') || loadedShipments[0]
    setSelectedShipment(active || null)

    const timer = setTimeout(() => setIsLoading(false), 800)
    return () => clearTimeout(timer)
  }, [])

  // Filter list
  const filteredShipments = shipments.filter(s => 
    s.id.toLowerCase().includes(search.toLowerCase()) ||
    s.orderNumber.toLowerCase().includes(search.toLowerCase()) ||
    s.origin.toLowerCase().includes(search.toLowerCase()) ||
    s.destination.toLowerCase().includes(search.toLowerCase())
  )

  // Map rendering coordinates helper
  const getCoordinates = (cityName: string) => {
    if (!cityName) return { x: 250, y: 150 }
    const cleanName = cityName.split(/[,,;]/)[0].trim().toLowerCase()
    const matchKey = Object.keys(cityCoordinates).find(
      key => key.toLowerCase() === cleanName
    )
    return matchKey ? cityCoordinates[matchKey] : { x: 250, y: 150 } // default center
  }

  const originCoords = selectedShipment ? getCoordinates(selectedShipment.origin) : { x: 0, y: 0 }
  const destCoords = selectedShipment ? getCoordinates(selectedShipment.destination) : { x: 0, y: 0 }
  
  // Calculate truck position (interpolated or from current city coords)
  let truckCoords = { x: 0, y: 0 }
  if (selectedShipment) {
    if (selectedShipment.status === 'Delivered') {
      truckCoords = destCoords
    } else if (selectedShipment.status === 'Pending') {
      truckCoords = originCoords
    } else if (selectedShipment.currentLocation?.city) {
      truckCoords = getCoordinates(`${selectedShipment.currentLocation.city}`)
    } else {
      // Interpolate center
      truckCoords = {
        x: (originCoords.x + destCoords.x) / 2,
        y: (originCoords.y + destCoords.y) / 2
      }
    }
  }

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <Skeleton className="h-10 w-full mb-4" />
            <div className="space-y-3">
              {[...Array(5)].map((_, i) => (
                <Skeleton key={i} className="h-16 w-full" />
              ))}
            </div>
          </div>
          <div className="lg:col-span-2 space-y-6">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <Skeleton className="h-[300px] w-full" />
            </div>
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <Skeleton className="h-48 w-full" />
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left Side: Shipment Selector list */}
        <div className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/70 dark:bg-slate-900/60 backdrop-blur-md p-4 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col h-[calc(100vh-12rem)] overflow-hidden animate-in fade-in slide-in-from-bottom-4">
          <div className="relative mb-4">
            <Search className="absolute top-1/2 left-3 h-4.5 w-4.5 -translate-y-1/2 text-slate-400 dark:text-slate-555" />
            <input
              type="text"
              placeholder="Search active routes..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 py-2 pr-4 pl-10 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
            />
          </div>

          <div className="flex-1 overflow-y-auto space-y-2 pr-1">
            {filteredShipments.map((shipment) => {
              const isSelected = selectedShipment?.id === shipment.id
              
              let statusColor = 'bg-slate-100 dark:bg-slate-850 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800'
              if (shipment.status === 'In Transit') {
                statusColor = 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/30'
              } else if (shipment.status === 'Delivered') {
                statusColor = 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/30'
              } else if (shipment.status === 'Delayed') {
                statusColor = 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-455 border-rose-200 dark:border-rose-900/30'
              }

              return (
                <button
                  key={shipment.id}
                  onClick={() => setSelectedShipment(shipment)}
                  className={`w-full text-left rounded-xl p-3 border transition-all cursor-pointer ${
                    isSelected 
                      ? 'border-blue-500 bg-blue-50/20 dark:bg-blue-950/20 shadow-sm' 
                      : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-850/40 hover:border-slate-300 dark:hover:border-slate-700'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <span className="font-bold text-slate-900 dark:text-slate-50 text-sm">{shipment.id}</span>
                    <span className={`inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold leading-none ${statusColor}`}>
                      {shipment.status}
                    </span>
                  </div>
                  <div className="mt-2 text-xs font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                    <span>{shipment.origin.split(',')[0]}</span>
                    <ArrowRight className="h-3 w-3 text-slate-450" />
                    <span>{shipment.destination.split(',')[0]}</span>
                  </div>
                  <div className="mt-1 flex items-center justify-between text-[10px] text-slate-450 dark:text-slate-500 font-medium">
                    <span>Carrier: {shipment.carrier}</span>
                    <span>Order: {shipment.orderNumber}</span>
                  </div>
                </button>
              )
            })}
          </div>
        </div>

        {/* Right Side: Map & Event Checkpoints */}
        <div className="lg:col-span-2 flex flex-col gap-6 h-[calc(100vh-12rem)] overflow-y-auto pr-1">
          {selectedShipment ? (
            <>
              {/* Map Panel */}
              <div className="rounded-2xl border border-slate-800 bg-[#0f172a]/75 backdrop-blur-md p-4 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col justify-between shrink-0 animate-in fade-in slide-in-from-bottom-4">
                <div className="flex items-center justify-between mb-4 text-white">
                  <div>
                    <h3 className="text-sm font-bold tracking-wide uppercase text-slate-400">Live Logistics Routing Map</h3>
                    <p className="text-xs text-slate-300">Route map for {selectedShipment.id} ({selectedShipment.carrier})</p>
                  </div>
                  <div className="flex items-center gap-2 text-xs font-semibold text-blue-400 bg-blue-900/30 border border-blue-800 px-3 py-1 rounded-full">
                    <Compass className="h-3.5 w-3.5 animate-spin duration-[4000ms]" />
                    Real-time position active
                  </div>
                </div>

                {/* SVG MAP VISUALIZATION */}
                <div className="relative bg-[#070b13]/90 rounded-xl overflow-hidden aspect-[16/9] border border-slate-800/80 flex items-center justify-center">
                  {/* Grid lines for style */}
                  <div className="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:30px_30px] opacity-15"></div>

                  <svg className="w-full h-full max-w-[600px] max-h-[350px]" viewBox="0 0 550 300">
                    {/* Draw Ghana border simulation (styled curved arcs for logistics feel) */}
                    <path 
                      d="M 140,40 L 320,40 L 350,150 L 340,260 L 160,265 L 130,150 Z" 
                      className="fill-none stroke-slate-800/60" 
                      strokeWidth="1.5" 
                      strokeDasharray="4 4"
                    />

                    {/* Pre-drawn regional hubs / major cities */}
                    {Object.entries(cityCoordinates).map(([name, coords]) => {
                      const isOrigin = selectedShipment && selectedShipment.origin.toLowerCase().includes(name.toLowerCase())
                      const isDest = selectedShipment && selectedShipment.destination.toLowerCase().includes(name.toLowerCase())
                      const isCurrent = selectedShipment && selectedShipment.currentLocation?.city?.toLowerCase() === name.toLowerCase()

                      if (isOrigin || isDest || isCurrent) return null

                      return (
                        <g key={name} className="opacity-35 hover:opacity-100 transition-opacity duration-300">
                          <circle cx={coords.x} cy={coords.y} r="2" className="fill-slate-650" />
                          <text x={coords.x + 5} y={coords.y + 2} className="fill-slate-550 text-[6px] font-semibold uppercase tracking-wider">{name}</text>
                        </g>
                      )
                    })}

                    {/* Routing Line for selected shipment */}
                    {/* Static track path */}
                    <path
                      d={`M ${originCoords.x},${originCoords.y} Q ${(originCoords.x + destCoords.x)/2},${(originCoords.y + destCoords.y)/2 - 25} ${destCoords.x},${destCoords.y}`}
                      className="fill-none stroke-slate-800/50"
                      strokeWidth="3"
                    />
                    {/* Animated route path */}
                    <path
                      d={`M ${originCoords.x},${originCoords.y} Q ${(originCoords.x + destCoords.x)/2},${(originCoords.y + destCoords.y)/2 - 25} ${destCoords.x},${destCoords.y}`}
                      className="fill-none stroke-blue-500/90 animate-dash"
                      strokeWidth="2"
                      strokeDasharray="8 6"
                    />

                    {/* Origin Pin */}
                    {selectedShipment.status === 'Pending' && (
                      <circle cx={originCoords.x} cy={originCoords.y} r="10" className="fill-blue-500/20 stroke-none animate-pulse" />
                    )}
                    <circle cx={originCoords.x} cy={originCoords.y} r="5" className="fill-blue-500 stroke-[#070b13]" strokeWidth="1.5" />
                    <text x={originCoords.x + 8} y={originCoords.y + 3} className="fill-blue-400 text-[8px] font-extrabold uppercase tracking-wider">{selectedShipment.origin.split(',')[0]}</text>

                    {/* Destination Pin */}
                    {selectedShipment.status === 'Delivered' && (
                      <circle cx={destCoords.x} cy={destCoords.y} r="10" className="fill-emerald-500/20 stroke-none animate-pulse" />
                    )}
                    <circle cx={destCoords.x} cy={destCoords.y} r="5" className="fill-emerald-500 stroke-[#070b13]" strokeWidth="1.5" />
                    <text x={destCoords.x + 8} y={destCoords.y + 3} className="fill-emerald-400 text-[8px] font-extrabold uppercase tracking-wider">{selectedShipment.destination.split(',')[0]}</text>

                    {/* Live Truck Indicator */}
                    {selectedShipment.status !== 'Pending' && selectedShipment.status !== 'Delivered' && (
                      <g>
                        <circle cx={truckCoords.x} cy={truckCoords.y} r="12" className="fill-blue-500/20 stroke-none animate-ping" style={{ animationDuration: '2s' }} />
                        <circle cx={truckCoords.x} cy={truckCoords.y} r="7" className="fill-blue-600 stroke-[#070b13]" strokeWidth="1.5" />
                        <path 
                          d="M -2.5 -2.5 L 2.5 2.5 M 2.5 -2.5 L -2.5 2.5" 
                          className="stroke-white" 
                          strokeWidth="1" 
                          transform={`translate(${truckCoords.x}, ${truckCoords.y})`}
                        />
                      </g>
                    )}
                  </svg>
                  
                  {/* Floating cargo details */}
                  <div className="absolute bottom-3 left-3 bg-[#0f172a]/95 border border-slate-800 px-3 py-2 rounded-xl text-[10px] text-slate-350 max-w-[180px] shadow-lg backdrop-blur-sm">
                    <p className="font-semibold text-white mb-0.5">Cargo Details</p>
                    <p>Weight: {selectedShipment.weight} lbs</p>
                    <p>Dimensions: {selectedShipment.dimensions.length}x{selectedShipment.dimensions.width}x{selectedShipment.dimensions.height} ft</p>
                    <p className="mt-1 font-semibold text-slate-400">ETA: {selectedShipment.eta}</p>
                  </div>

                  <div className="absolute bottom-3 right-3 bg-[#0f172a]/95 border border-slate-800 px-3 py-2 rounded-xl text-[10px] text-slate-350 shadow-lg backdrop-blur-sm">
                    {selectedShipment.currentLocation?.city ? (
                      <p className="font-semibold text-white">Current: <span className="text-blue-400">{selectedShipment.currentLocation.city}</span></p>
                    ) : (
                      <p className="font-semibold text-slate-400">Awaiting Dispatch</p>
                    )}
                  </div>
                </div>
              </div>

              {/* Event Timeline Checkpoints */}
              <div className="rounded-2xl border border-white/40 dark:border-white/10 bg-white/70 dark:bg-slate-900/60 backdrop-blur-md p-6 shadow-sm hover:shadow-md transition-all duration-300 flex-1 animate-in fade-in slide-in-from-bottom-4">
                <div className="flex items-center justify-between mb-6">
                  <div>
                    <h3 className="text-base font-bold text-slate-900 dark:text-white">Shipment Timeline Events</h3>
                    <p className="text-xs text-slate-550 dark:text-slate-400">Chronological history of shipment transit checkpoints</p>
                  </div>
                </div>

                <div className="relative pl-6 border-l-2 border-slate-100 dark:border-slate-800 space-y-6 ml-2.5">
                  {selectedShipment.events && selectedShipment.events.length > 0 ? (
                    selectedShipment.events.map((event, idx) => {
                      const isLast = idx === selectedShipment.events.length - 1
                      
                      let nodeColor = 'bg-slate-250 dark:bg-slate-800 border-slate-100 dark:border-slate-900 text-slate-655 dark:text-slate-400'
                      if (event.status === 'Delivered') {
                        nodeColor = 'bg-emerald-500 border-white text-white'
                      } else if (event.status === 'Delayed') {
                        nodeColor = 'bg-rose-500 border-white text-white'
                      } else if (isLast) {
                        nodeColor = 'bg-blue-600 border-white text-white font-bold'
                      }

                      return (
                        <div key={event.id} className="relative">
                          {/* Dot marker */}
                          <span className={`absolute -left-[31px] top-0.5 flex h-4 w-4 items-center justify-center rounded-full border-2 ${nodeColor}`}>
                          </span>
                          
                          <div className="flex flex-col md:flex-row md:items-baseline justify-between gap-1.5">
                            <span className={`text-xs font-semibold uppercase tracking-wider ${isLast ? 'text-blue-600 dark:text-blue-400' : 'text-slate-800 dark:text-slate-200'}`}>
                              {event.status} - {event.location}
                            </span>
                            <span className="text-[10px] text-slate-400 dark:text-slate-500 font-mono font-medium">{event.timestamp}</span>
                          </div>
                          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-xl">
                            {event.description}
                          </p>
                        </div>
                      )
                    })
                  ) : (
                    <p className="text-slate-400 dark:text-slate-550 text-xs py-4 font-semibold">No timeline events have been logged yet.</p>
                  )}
                </div>
              </div>
            </>
          ) : (
            <div className="flex flex-1 flex-col items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 p-8 text-center shadow-sm">
              <Truck className="h-12 w-12 text-slate-300 dark:text-slate-600 mb-3" />
              <p className="text-slate-500 dark:text-slate-400 font-semibold text-sm">Select a shipment to begin tracking</p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
