export interface TrackingEvent {
  id: string
  timestamp: string
  location: string
  description: string
  status: 'Pending' | 'In Transit' | 'Delivered' | 'Delayed' | 'Dispatched' | 'Arrived' | 'Departed' | 'Out for Delivery'
}

export interface Shipment {
  id: string
  orderNumber: string
  origin: string
  destination: string
  status: 'Pending' | 'In Transit' | 'Delivered' | 'Delayed'
  carrier: string
  weight: number // in lbs
  dimensions: {
    length: number
    width: number
    height: number
  }
  pickupDate: string
  deliveryDate: string
  eta: string
  currentLocation?: {
    lat: number
    lng: number
    city?: string
  }
  events: TrackingEvent[]
}

export interface Driver {
  id: string
  name: string
  vehicleId: string
  vehicleName: string
  vehicleType: 'Semi-Truck' | 'Box Truck' | 'Cargo Van'
  status: 'Active' | 'Off Duty' | 'Maintenance'
  contact: string
  avatar?: string
}

export interface MetricSummary {
  totalShipments: number
  onTimeDeliveryRate: number
  activeDrivers: number
  totalRevenue: number
  revenueChange: number // percentage change
  shipmentChange: number // percentage change
}
