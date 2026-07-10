import React, { useState, useEffect } from 'react'
import { 
  Plus, 
  Search, 
  ArrowUpDown, 
  Edit2, 
  Trash2, 
  X, 
  Filter, 
  Calendar,
  Layers,
  Scale,
  MapPin,
  Tag
} from 'lucide-react'
import { mockShipments } from '../data/mockData'
import type { Shipment, TrackingEvent } from '../types/tms'
import { Skeleton } from '../components/ui/skeleton'

export default function Shipments() {
  const [isLoading, setIsLoading] = useState(true)
  const [shipments, setShipments] = useState<Shipment[]>(() => {
    const saved = localStorage.getItem('tms_shipments')
    return saved ? JSON.parse(saved) : mockShipments
  })

  // Filter states
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('All')
  
  // Sort states
  const [sortField, setSortField] = useState<keyof Shipment>('id')
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc')

  // Modal form states
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingShipment, setEditingShipment] = useState<Shipment | null>(null)
  
  // Form fields
  const [orderNumber, setOrderNumber] = useState('')
  const [origin, setOrigin] = useState('')
  const [destination, setDestination] = useState('')
  const [carrier, setCarrier] = useState('Apex Freight')
  const [status, setStatus] = useState<'Pending' | 'In Transit' | 'Delivered' | 'Delayed'>('Pending')
  const [weight, setWeight] = useState('')
  const [length, setLength] = useState('')
  const [width, setWidth] = useState('')
  const [height, setHeight] = useState('')
  const [pickupDate, setPickupDate] = useState('')
  const [deliveryDate, setDeliveryDate] = useState('')

  // Form error state
  const [formError, setFormError] = useState('')

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 700)
    return () => clearTimeout(timer)
  }, [])

  // Persist shipments in localStorage
  useEffect(() => {
    localStorage.setItem('tms_shipments', JSON.stringify(shipments))
  }, [shipments])

  // Open modal for Creating new shipment
  const handleCreateOpen = () => {
    setEditingShipment(null)
    setOrderNumber('')
    setOrigin('')
    setDestination('')
    setCarrier('Apex Freight')
    setStatus('Pending')
    setWeight('')
    setLength('')
    setWidth('')
    setHeight('')
    
    // Set default dates to today/tomorrow
    const today = new Date().toISOString().split('T')[0]
    const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0]
    setPickupDate(today)
    setDeliveryDate(tomorrow)
    
    setFormError('')
    setIsModalOpen(true)
  }

  // Open modal for Editing existing shipment
  const handleEditOpen = (shipment: Shipment) => {
    setEditingShipment(shipment)
    setOrderNumber(shipment.orderNumber)
    setOrigin(shipment.origin)
    setDestination(shipment.destination)
    setCarrier(shipment.carrier)
    setStatus(shipment.status)
    setWeight(shipment.weight.toString())
    setLength(shipment.dimensions.length.toString())
    setWidth(shipment.dimensions.width.toString())
    setHeight(shipment.dimensions.height.toString())
    setPickupDate(shipment.pickupDate)
    setDeliveryDate(shipment.deliveryDate)
    setFormError('')
    setIsModalOpen(true)
  }

  // Delete shipment handler
  const handleDeleteShipment = (id: string) => {
    if (window.confirm(`Are you sure you want to delete shipment ${id}?`)) {
      setShipments(shipments.filter(s => s.id !== id))
    }
  }

  // Form submit handler
  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    
    // Simple validation
    if (!orderNumber || !origin || !destination || !weight || !length || !width || !height || !pickupDate || !deliveryDate) {
      setFormError('Please fill out all required fields.')
      return
    }

    const parsedWeight = parseFloat(weight)
    const parsedLength = parseFloat(length)
    const parsedWidth = parseFloat(width)
    const parsedHeight = parseFloat(height)

    if (isNaN(parsedWeight) || isNaN(parsedLength) || isNaN(parsedWidth) || isNaN(parsedHeight)) {
      setFormError('Weight and dimensions must be valid numbers.')
      return
    }

    const formattedEta = `${deliveryDate} 17:00`

    if (editingShipment) {
      // Update existing
      const updatedShipments = shipments.map((s) => {
        if (s.id === editingShipment.id) {
          // If status changed, push a new event
          const updatedEvents = [...s.events]
          if (s.status !== status) {
            const newEvent: TrackingEvent = {
              id: `EV-${Date.now()}`,
              timestamp: new Date().toISOString().replace('T', ' ').slice(0, 16),
              location: status === 'Delivered' ? destination : origin,
              description: `Status updated manually to ${status}.`,
              status: status as any
            }
            updatedEvents.push(newEvent)
          }

          return {
            ...s,
            orderNumber,
            origin,
            destination,
            carrier,
            status,
            weight: parsedWeight,
            dimensions: { length: parsedLength, width: parsedWidth, height: parsedHeight },
            pickupDate,
            deliveryDate,
            eta: formattedEta,
            events: updatedEvents
          }
        }
        return s
      })
      setShipments(updatedShipments)
    } else {
      // Create new
      const nextIdNum = shipments.reduce((max, s) => {
        const idParts = s.id.split('-')
        const num = parseInt(idParts[1])
        return num > max ? num : max
      }, 1000) + 1

      const newShipment: Shipment = {
        id: `SH-${nextIdNum}`,
        orderNumber,
        origin,
        destination,
        status,
        carrier,
        weight: parsedWeight,
        dimensions: { length: parsedLength, width: parsedWidth, height: parsedHeight },
        pickupDate,
        deliveryDate,
        eta: formattedEta,
        currentLocation: {
          lat: 7.9465, // Center of Ghana
          lng: -1.0232,
          city: origin.split(',')[0]
        },
        events: [
          {
            id: `EV-${Date.now()}`,
            timestamp: new Date().toISOString().replace('T', ' ').slice(0, 16),
            location: origin,
            description: 'Shipment created and registered in routing center.',
            status: 'Pending'
          }
        ]
      }

      setShipments([newShipment, ...shipments])
    }

    setIsModalOpen(false)
  }

  // Sort helper
  const handleSort = (field: keyof Shipment) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortDirection('asc')
    }
  }

  // Filter and Sort implementation
  const filteredShipments = shipments
    .filter((s) => {
      const matchSearch = 
        s.id.toLowerCase().includes(search.toLowerCase()) ||
        s.orderNumber.toLowerCase().includes(search.toLowerCase()) ||
        s.origin.toLowerCase().includes(search.toLowerCase()) ||
        s.destination.toLowerCase().includes(search.toLowerCase()) ||
        s.carrier.toLowerCase().includes(search.toLowerCase())
      
      const matchStatus = statusFilter === 'All' || s.status === statusFilter

      return matchSearch && matchStatus
    })
    .sort((a, b) => {
      let aVal = a[sortField]
      let bVal = b[sortField]

      // Handle simple string comparison
      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return sortDirection === 'asc' 
          ? aVal.localeCompare(bVal)
          : bVal.localeCompare(aVal)
      }
      
      // Handle numeric fields
      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return sortDirection === 'asc' ? aVal - bVal : bVal - aVal
      }

      return 0
    })

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <Skeleton className="h-10 w-64" />
          <Skeleton className="h-10 w-36 rounded-xl" />
        </div>
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex gap-4 mb-6">
            <Skeleton className="h-10 flex-1" />
            <Skeleton className="h-10 w-32" />
          </div>
          <div className="space-y-4">
            {[...Array(6)].map((_, i) => (
              <Skeleton key={i} className="h-12 w-full" />
            ))}
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Title & Action Button */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-xs font-semibold text-slate-400 uppercase tracking-widest">Directory Panel</p>
          <p className="text-lg font-semibold text-slate-500">Manage all carrier bookings and schedules</p>
        </div>
        <button
          onClick={handleCreateOpen}
          className="flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-600/15 hover:bg-blue-700 transition-all cursor-pointer"
        >
          <Plus className="h-4.5 w-4.5" />
          Create Shipment
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col gap-4 md:flex-row md:items-center justify-between bg-white/70 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl p-4 shadow-sm backdrop-blur-md hover:shadow-md transition-all duration-300 animate-in fade-in slide-in-from-bottom-4">
        <div className="relative flex-1">
          <Search className="absolute top-1/2 left-3 h-4.5 w-4.5 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
          <input
            type="text"
            placeholder="Search by ID, Order #, Route, Carrier..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 py-2 pr-4 pl-10 text-sm text-slate-800 dark:text-slate-200 transition-all focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
          />
        </div>

        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-slate-400" />
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 focus:border-blue-500 focus:outline-none"
          >
            <option value="All">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="In Transit">In Transit</option>
            <option value="Delivered">Delivered</option>
            <option value="Delayed">Delayed</option>
          </select>
        </div>
      </div>

      {/* Shipments Table Container */}
      <div className="overflow-hidden rounded-2xl border border-white/40 dark:border-white/10 bg-white/70 dark:bg-slate-900/60 shadow-sm backdrop-blur-md animate-in fade-in slide-in-from-bottom-4 transition-all duration-300 hover:shadow-md">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left text-sm text-slate-655 dark:text-slate-400">
            <thead className="bg-slate-50 dark:bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th onClick={() => handleSort('id')} className="py-4 px-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                  <div className="flex items-center gap-1">
                    Shipment ID
                    <ArrowUpDown className="h-3.5 w-3.5" />
                  </div>
                </th>
                <th onClick={() => handleSort('orderNumber')} className="py-4 px-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                  <div className="flex items-center gap-1">
                    Order No.
                    <ArrowUpDown className="h-3.5 w-3.5" />
                  </div>
                </th>
                <th onClick={() => handleSort('origin')} className="py-4 px-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                  <div className="flex items-center gap-1">
                    Route (Origin → Dest)
                    <ArrowUpDown className="h-3.5 w-3.5" />
                  </div>
                </th>
                <th onClick={() => handleSort('status')} className="py-4 px-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                  <div className="flex items-center gap-1">
                    Status
                    <ArrowUpDown className="h-3.5 w-3.5" />
                  </div>
                </th>
                <th onClick={() => handleSort('carrier')} className="py-4 px-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                  <div className="flex items-center gap-1">
                    Carrier
                    <ArrowUpDown className="h-3.5 w-3.5" />
                  </div>
                </th>
                <th onClick={() => handleSort('deliveryDate')} className="py-4 px-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                  <div className="flex items-center gap-1">
                    ETA / Delivery Date
                    <ArrowUpDown className="h-3.5 w-3.5" />
                  </div>
                </th>
                <th className="py-4 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {filteredShipments.length > 0 ? (
                filteredShipments.map((shipment) => {
                  // Status Badge Styling
                  let badgeClass = 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                  if (shipment.status === 'In Transit') {
                    badgeClass = 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/30'
                  } else if (shipment.status === 'Delivered') {
                    badgeClass = 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/30'
                  } else if (shipment.status === 'Delayed') {
                    badgeClass = 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900/30'
                  }

                  return (
                    <tr key={shipment.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition-colors">
                      <td className="py-4 px-6 font-semibold text-slate-900 dark:text-slate-50">{shipment.id}</td>
                      <td className="py-4 px-6 text-slate-500 dark:text-slate-400 font-mono text-xs">{shipment.orderNumber}</td>
                      <td className="py-4 px-6">
                        <div className="flex flex-col">
                          <span className="font-semibold text-slate-800 dark:text-slate-200">{shipment.origin}</span>
                          <span className="text-xs text-slate-400 dark:text-slate-500 mt-0.5">to {shipment.destination}</span>
                        </div>
                      </td>
                      <td className="py-4 px-6">
                        <span className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold leading-none ${badgeClass}`}>
                          {shipment.status}
                        </span>
                      </td>
                      <td className="py-4 px-6 text-slate-700 dark:text-slate-300 font-medium">{shipment.carrier}</td>
                      <td className="py-4 px-6 text-slate-655 dark:text-slate-350 font-medium">{shipment.deliveryDate}</td>
                      <td className="py-4 px-6 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => handleEditOpen(shipment)}
                            className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200 transition-colors"
                            title="Edit"
                          >
                            <Edit2 className="h-4 w-4" />
                          </button>
                          <button
                            onClick={() => handleDeleteShipment(shipment.id)}
                            className="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-650 dark:hover:text-red-400 transition-colors"
                            title="Delete"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })
              ) : (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-400 dark:text-slate-500 font-semibold">
                    No shipments found matching the query.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Dynamic Modal Dialog for Create/Edit */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm animate-in fade-in duration-300">
          <div className="relative w-full max-w-2xl rounded-2xl bg-white/80 dark:bg-slate-900/90 p-6 shadow-2xl border border-white/40 dark:border-white/10 max-h-[90svh] overflow-y-auto backdrop-blur-lg animate-in fade-in zoom-in-95 slide-in-from-bottom-4 duration-300">
            {/* Modal Header */}
            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
              <div>
                <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                  {editingShipment ? `Edit Shipment - ${editingShipment.id}` : 'Create New Booking'}
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Configure shipment routes, details, and dispatch options
                </p>
              </div>
              <button
                onClick={() => setIsModalOpen(false)}
                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {/* Error Notification */}
            {formError && (
              <div className="mb-4 rounded-xl bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 p-3 text-xs text-red-700 dark:text-red-400 font-medium">
                {formError}
              </div>
            )}

            {/* Form */}
            <form onSubmit={handleFormSubmit} className="space-y-4">
              {/* Row 1: Order Info */}
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Order Number *
                  </label>
                  <div className="relative">
                    <Tag className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      type="text"
                      required
                      placeholder="e.g. ORD-9821"
                      value={orderNumber}
                      onChange={(e) => setOrderNumber(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 pl-9 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Carrier Name
                  </label>
                  <select
                    value={carrier}
                    onChange={(e) => setCarrier(e.target.value)}
                    className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                  >
                    <option value="Apex Freight">Apex Freight</option>
                    <option value="Swift Logistics">Swift Logistics</option>
                    <option value="BlueSky Transport">BlueSky Transport</option>
                    <option value="Titan Cargo">Titan Cargo</option>
                    <option value="Atlas Shipping">Atlas Shipping</option>
                  </select>
                </div>
              </div>

              {/* Row 2: Route Addresses */}
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Pick-up Address *
                  </label>
                  <div className="relative">
                    <MapPin className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      type="text"
                      required
                      placeholder="e.g. Seattle, WA"
                      value={origin}
                      onChange={(e) => setOrigin(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 pl-9 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Delivery Address *
                  </label>
                  <div className="relative">
                    <MapPin className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      type="text"
                      required
                      placeholder="e.g. Dallas, TX"
                      value={destination}
                      onChange={(e) => setDestination(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 pl-9 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                    />
                  </div>
                </div>
              </div>

              {/* Row 3: Physical Cargo Specs */}
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Weight (lbs) *
                  </label>
                  <div className="relative">
                    <Scale className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      type="number"
                      required
                      placeholder="2000"
                      value={weight}
                      onChange={(e) => setWeight(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 pl-9 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Length (ft) *
                  </label>
                  <div className="relative">
                    <Layers className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      type="number"
                      required
                      placeholder="48"
                      value={length}
                      onChange={(e) => setLength(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 pl-9 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Width (ft) *
                  </label>
                  <input
                    type="number"
                    required
                    placeholder="8.5"
                    value={width}
                    onChange={(e) => setWidth(e.target.value)}
                    className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Height (ft) *
                  </label>
                  <input
                    type="number"
                    required
                    placeholder="9.5"
                    value={height}
                    onChange={(e) => setHeight(e.target.value)}
                    className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                  />
                </div>
              </div>

              {/* Row 4: Dates & Status */}
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Pickup Date *
                  </label>
                  <div className="relative">
                    <Calendar className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      type="date"
                      required
                      value={pickupDate}
                      onChange={(e) => setPickupDate(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 pl-9 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Delivery Date *
                  </label>
                  <div className="relative">
                    <Calendar className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      type="date"
                      required
                      value={deliveryDate}
                      onChange={(e) => setDeliveryDate(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 pl-9 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Current Status
                  </label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none"
                  >
                    <option value="Pending">Pending</option>
                    <option value="In Transit">In Transit</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Delayed">Delayed</option>
                  </select>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800 pt-4 mt-6">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="rounded-xl border border-slate-200 dark:border-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-655 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/10 hover:bg-blue-700 transition-all cursor-pointer"
                >
                  Save Shipment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
