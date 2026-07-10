import type { Shipment, Driver, MetricSummary } from '../types/tms'

export const mockMetrics: MetricSummary = {
  totalShipments: 1248,
  onTimeDeliveryRate: 98.4,
  activeDrivers: 42,
  totalRevenue: 245900,
  revenueChange: 12.8,
  shipmentChange: 8.5
}

export const mockDrivers: Driver[] = [
  {
    id: 'DRV-01',
    name: 'Kwame Mensah',
    vehicleId: 'GW-2904-22',
    vehicleName: 'DAF XF 105',
    vehicleType: 'Semi-Truck',
    status: 'Active',
    contact: '+233 24 123 4567',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
  },
  {
    id: 'DRV-02',
    name: 'Kojo Boateng',
    vehicleId: 'GT-8412-25',
    vehicleName: 'Mercedes-Benz Actros',
    vehicleType: 'Semi-Truck',
    status: 'Active',
    contact: '+233 20 234 5678',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
  },
  {
    id: 'DRV-03',
    name: 'Kofi Hanson',
    vehicleId: 'AS-1024-23',
    vehicleName: 'Toyota Dyna Box',
    vehicleType: 'Box Truck',
    status: 'Active',
    contact: '+233 27 345 6789',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
  },
  {
    id: 'DRV-04',
    name: 'Yaw Addo',
    vehicleId: 'ER-4521-24',
    vehicleName: 'Hyundai H100',
    vehicleType: 'Cargo Van',
    status: 'Off Duty',
    contact: '+233 55 456 7890',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
  },
  {
    id: 'DRV-05',
    name: 'Amma Osei',
    vehicleId: 'GT-1102-23',
    vehicleName: 'MAN TGX 26.440',
    vehicleType: 'Semi-Truck',
    status: 'Maintenance',
    contact: '+233 24 567 8901',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
  },
  {
    id: 'DRV-06',
    name: 'Abena Appiah',
    vehicleId: 'AS-9921-22',
    vehicleName: 'Kia Bongo III',
    vehicleType: 'Box Truck',
    status: 'Active',
    contact: '+233 26 678 9012',
    avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
  }
]

export const mockShipments: Shipment[] = [
  {
    id: 'SH-1021',
    orderNumber: 'ORD-87321',
    origin: 'Accra',
    destination: 'Tamale',
    status: 'In Transit',
    carrier: 'Accra Express Freight',
    weight: 24500,
    dimensions: { length: 48, width: 8.5, height: 9.5 },
    pickupDate: '2026-06-23',
    deliveryDate: '2026-06-27',
    eta: '2026-06-27 14:30',
    currentLocation: { lat: 6.6666, lng: -1.6163, city: 'Kumasi' },
    events: [
      {
        id: 'EV-101',
        timestamp: '2026-06-23 08:00',
        location: 'Accra',
        description: 'Shipment loaded and dispatched from Accra Central Yard.',
        status: 'Dispatched'
      },
      {
        id: 'EV-102',
        timestamp: '2026-06-24 12:15',
        location: 'Cape Coast',
        description: 'Arrived at Cape Coast terminal. Driver changeover completed.',
        status: 'Arrived'
      },
      {
        id: 'EV-103',
        timestamp: '2026-06-24 14:00',
        location: 'Cape Coast',
        description: 'Departed Cape Coast, heading North via N6.',
        status: 'Departed'
      },
      {
        id: 'EV-104',
        timestamp: '2026-06-25 07:45',
        location: 'Kumasi',
        description: 'Cargo checked at Kumasi scale house. Resumed transit.',
        status: 'In Transit'
      }
    ]
  },
  {
    id: 'SH-1022',
    orderNumber: 'ORD-98244',
    origin: 'Takoradi',
    destination: 'Accra',
    status: 'In Transit',
    carrier: 'Gold Coast Haulers',
    weight: 18200,
    dimensions: { length: 24, width: 8.0, height: 8.5 },
    pickupDate: '2026-06-24',
    deliveryDate: '2026-06-28',
    eta: '2026-06-28 09:15',
    currentLocation: { lat: 5.1053, lng: -1.2466, city: 'Cape Coast' },
    events: [
      {
        id: 'EV-201',
        timestamp: '2026-06-24 09:30',
        location: 'Takoradi',
        description: 'Cargo inspected and loaded at Port of Takoradi.',
        status: 'Dispatched'
      },
      {
        id: 'EV-202',
        timestamp: '2026-06-25 06:10',
        location: 'Cape Coast',
        description: 'Departed Cape Coast depot after safety check.',
        status: 'Departed'
      }
    ]
  },
  {
    id: 'SH-1023',
    orderNumber: 'ORD-11204',
    origin: 'Tema',
    destination: 'Kumasi',
    status: 'Delivered',
    carrier: 'Black Star Logistics',
    weight: 12000,
    dimensions: { length: 20, width: 8.0, height: 8.0 },
    pickupDate: '2026-06-22',
    deliveryDate: '2026-06-24',
    eta: '2026-06-24 16:00',
    currentLocation: { lat: 6.6666, lng: -1.6163, city: 'Kumasi' },
    events: [
      {
        id: 'EV-301',
        timestamp: '2026-06-22 10:00',
        location: 'Tema',
        description: 'Shipment loaded at Tema Harbour Yard.',
        status: 'Dispatched'
      },
      {
        id: 'EV-302',
        timestamp: '2026-06-23 09:30',
        location: 'Accra',
        description: 'Bypassed Accra toll booth. Driver changeover completed.',
        status: 'In Transit'
      },
      {
        id: 'EV-303',
        timestamp: '2026-06-23 22:00',
        location: 'Koforidua',
        description: 'Entered Eastern corridor checkpoint.',
        status: 'In Transit'
      },
      {
        id: 'EV-304',
        timestamp: '2026-06-24 15:42',
        location: 'Kumasi',
        description: 'Delivered successfully. Signed off by K. Osei.',
        status: 'Delivered'
      }
    ]
  },
  {
    id: 'SH-1024',
    orderNumber: 'ORD-76612',
    origin: 'Tamale',
    destination: 'Accra',
    status: 'Delayed',
    carrier: 'West Africa Transit',
    weight: 35000,
    dimensions: { length: 53, width: 8.5, height: 9.5 },
    pickupDate: '2026-06-22',
    deliveryDate: '2026-06-25',
    eta: '2026-06-25 18:00',
    currentLocation: { lat: 7.3349, lng: -2.3123, city: 'Sunyani' },
    events: [
      {
        id: 'EV-401',
        timestamp: '2026-06-22 14:00',
        location: 'Tamale',
        description: 'Departed Tamale airport cargo terminal.',
        status: 'Dispatched'
      },
      {
        id: 'EV-402',
        timestamp: '2026-06-23 18:30',
        location: 'Techiman',
        description: 'Stopped for engine inspection due to dashboard warning light.',
        status: 'Delayed'
      },
      {
        id: 'EV-403',
        timestamp: '2026-06-24 11:00',
        location: 'Sunyani',
        description: 'Maintenance completed (radiator leak fixed). Dispatch delayed.',
        status: 'Delayed'
      }
    ]
  },
  {
    id: 'SH-1025',
    orderNumber: 'ORD-54412',
    origin: 'Accra',
    destination: 'Takoradi',
    status: 'Pending',
    carrier: 'Black Star Logistics',
    weight: 8500,
    dimensions: { length: 16, width: 7.5, height: 7.5 },
    pickupDate: '2026-06-26',
    deliveryDate: '2026-06-28',
    eta: '2026-06-28 12:00',
    currentLocation: { lat: 5.6037, lng: -0.1870, city: 'Accra' },
    events: [
      {
        id: 'EV-501',
        timestamp: '2026-06-25 06:00',
        location: 'Accra',
        description: 'Shipment booked. Awaiting pickup scheduled for tomorrow.',
        status: 'Pending'
      }
    ]
  },
  {
    id: 'SH-1026',
    orderNumber: 'ORD-22998',
    origin: 'Ho',
    destination: 'Accra',
    status: 'Delivered',
    carrier: 'Accra Express Freight',
    weight: 29800,
    dimensions: { length: 48, width: 8.5, height: 9.0 },
    pickupDate: '2026-06-20',
    deliveryDate: '2026-06-23',
    eta: '2026-06-23 11:45',
    currentLocation: { lat: 5.6037, lng: -0.1870, city: 'Accra' },
    events: [
      {
        id: 'EV-601',
        timestamp: '2026-06-20 08:30',
        location: 'Ho',
        description: 'Dispatched from Ho regional depot.',
        status: 'Dispatched'
      },
      {
        id: 'EV-602',
        timestamp: '2026-06-23 11:38',
        location: 'Accra',
        description: 'Delivered to Accra Industrial Area. Manifest signed.',
        status: 'Delivered'
      }
    ]
  },
  {
    id: 'SH-1027',
    orderNumber: 'ORD-30114',
    origin: 'Kumasi',
    destination: 'Sunyani',
    status: 'Pending',
    carrier: 'Volta Cargo Carriers',
    weight: 5400,
    dimensions: { length: 12, width: 7.0, height: 7.0 },
    pickupDate: '2026-06-26',
    deliveryDate: '2026-06-27',
    eta: '2026-06-27 15:00',
    currentLocation: { lat: 6.6666, lng: -1.6163, city: 'Kumasi' },
    events: [
      {
        id: 'EV-701',
        timestamp: '2026-06-25 07:12',
        location: 'Kumasi',
        description: 'Shipment details registered. Carrier assigned.',
        status: 'Pending'
      }
    ]
  },
  {
    id: 'SH-1028',
    orderNumber: 'ORD-88120',
    origin: 'Tema',
    destination: 'Tamale',
    status: 'In Transit',
    carrier: 'Gold Coast Haulers',
    weight: 22000,
    dimensions: { length: 40, width: 8.5, height: 9.0 },
    pickupDate: '2026-06-23',
    deliveryDate: '2026-06-26',
    eta: '2026-06-26 16:30',
    currentLocation: { lat: 6.6666, lng: -1.6163, city: 'Kumasi' },
    events: [
      {
        id: 'EV-801',
        timestamp: '2026-06-23 16:00',
        location: 'Tema',
        description: 'Dispatched from Tema port terminal.',
        status: 'Dispatched'
      },
      {
        id: 'EV-802',
        timestamp: '2026-06-24 10:00',
        location: 'Koforidua',
        description: 'Refueling stops completed at Eastern bypass.',
        status: 'In Transit'
      },
      {
        id: 'EV-803',
        timestamp: '2026-06-25 06:40',
        location: 'Kumasi',
        description: 'En route north on N10. Passing Kumasi toll booth.',
        status: 'In Transit'
      }
    ]
  }
]

export const mockRecentActivity = [
  {
    id: 'ACT-01',
    time: '5 mins ago',
    type: 'delivery',
    message: 'Shipment SH-1026 has been successfully delivered to Accra.',
    user: 'Driver Kofi H.'
  },
  {
    id: 'ACT-02',
    time: '23 mins ago',
    type: 'status_change',
    message: 'Shipment SH-1024 status updated to DELAYED (Radiator leak at Techiman).',
    user: 'Operations Coordinator'
  },
  {
    id: 'ACT-03',
    time: '1 hour ago',
    type: 'creation',
    message: 'New shipment SH-1028 created for Tema to Tamale.',
    user: 'System Broker'
  },
  {
    id: 'ACT-04',
    time: '2 hours ago',
    type: 'driver_assign',
    message: 'Driver Kojo Boateng assigned to vehicle VEH-45 (Mercedes-Benz Actros).',
    user: 'Fleet Manager'
  },
  {
    id: 'ACT-05',
    time: '4 hours ago',
    type: 'customs',
    message: 'Manifest cleared customs for outbound Tema Harbour shipment SH-1022.',
    user: 'Brokerage Agent'
  }
]
