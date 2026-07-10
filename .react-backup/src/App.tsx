import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Layout from './components/Layout'
import Dashboard from './pages/Dashboard'
import Shipments from './pages/Shipments'
import Tracking from './pages/Tracking'
import Drivers from './pages/Drivers'
import Login from './pages/Login'
import Landing from './pages/Landing'
import { ErrorBoundary } from './components/ErrorBoundary'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Public Routes */}
        <Route path="/" element={<Landing />} />
        <Route path="/login" element={<Login />} />

        {/* Authenticated Application Console */}
        <Route path="/app" element={<Layout />}>
          <Route 
            index 
            element={
              <ErrorBoundary>
                <Dashboard />
              </ErrorBoundary>
            } 
          />
          <Route 
            path="shipments" 
            element={
              <ErrorBoundary>
                <Shipments />
              </ErrorBoundary>
            } 
          />
          <Route 
            path="tracking" 
            element={
              <ErrorBoundary>
                <Tracking />
              </ErrorBoundary>
            } 
          />
          <Route 
            path="drivers" 
            element={
              <ErrorBoundary>
                <Drivers />
              </ErrorBoundary>
            } 
          />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
