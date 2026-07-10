import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { 
  Truck, 
  ArrowRight, 
  ShieldCheck, 
  Globe, 
  Compass, 
  Users, 
  Terminal, 
  Activity, 
  MapPin, 
  Settings, 
  Layers, 
  PhoneCall 
} from 'lucide-react'
import logoImg from '../assets/Logo1.png'
import bgImg from '../assets/image2.jpg'

export default function Landing() {
  const navigate = useNavigate()
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  
  // Cargo Simulator States
  const [simStep, setSimStep] = useState<0 | 1 | 2 | 3>(0)
  const [simRunning, setSimRunning] = useState(false)
  const [simLog, setSimLog] = useState<string[]>([
    'System standby. Awaiting dispatcher instructions...'
  ])

  useEffect(() => {
    setIsAuthenticated(localStorage.getItem('tms_authenticated') === 'true')
  }, [])

  const handleCtaClick = () => {
    if (isAuthenticated) {
      navigate('/app')
    } else {
      navigate('/login')
    }
  }

  // Interactive Simulator Trigger
  const runSimulator = () => {
    if (simRunning) return
    setSimRunning(true)
    setSimStep(0)
    setSimLog(['[SYSTEM] Initializing dispatcher simulation node...'])

    setTimeout(() => {
      setSimStep(1)
      setSimLog(prev => [
        ...prev,
        '[DISPATCH] Order ORD-8472 loaded. Weight: 42,500 lbs.',
        '[ROUTE] Generating optimal Accra → Tamale transit corridor...'
      ])
    }, 1200)

    setTimeout(() => {
      setSimStep(2)
      setSimLog(prev => [
        ...prev,
        '[TRANSIT] Driver assigned. Black Star DAF #2904 is rolling.',
        '[GPS] Position locked. Passing Kumasi Bypass, Ashanti Region.'
      ])
    }, 3000)

    setTimeout(() => {
      setSimStep(3)
      setSimRunning(false)
      setSimLog(prev => [
        ...prev,
        '[DELIVERED] Tamale terminal reached. Cargo checked.',
        '[COMPLETED] Digital manifest signed. POD uploaded. Operations standby.'
      ])
    }, 5000)
  }

  return (
    <div 
      className="min-h-screen w-screen bg-[#070b13] text-slate-100 font-sans antialiased relative overflow-x-hidden selection:bg-blue-500/30 selection:text-white bg-cover bg-center bg-no-repeat"
      style={{ backgroundImage: `linear-gradient(to bottom, rgba(7, 11, 19, 0.5), rgba(7, 11, 19, 0.6)), url(${bgImg})` }}
    >
      {/* Dynamic Background Effects */}
      <div className="absolute top-[-10%] left-[-10%] w-[600px] h-[600px] bg-blue-500/10 rounded-full filter blur-[150px] pointer-events-none animate-pulse duration-[8000ms]"></div>
      <div className="absolute bottom-[20%] right-[-10%] w-[600px] h-[600px] bg-emerald-500/5 rounded-full filter blur-[150px] pointer-events-none animate-pulse duration-[10000ms]"></div>
      <div className="absolute top-[40%] right-[10%] w-[500px] h-[500px] bg-purple-500/5 rounded-full filter blur-[150px] pointer-events-none"></div>

      {/* Grid Pattern Overlay */}
      <div className="absolute inset-0 bg-[linear-gradient(to_right,#161f38_1px,transparent_1px),linear-gradient(to_bottom,#161f38_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-35 pointer-events-none"></div>

      {/* Navigation Header */}
      <header className="sticky top-0 z-50 w-full bg-[#070b13]/85 backdrop-blur-md border-b border-slate-900/60 transition-colors">
        <div className="mx-auto flex max-w-7xl h-20 items-center justify-between px-6">
          {/* Brand */}
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 p-1.5 border border-white/10 shadow-lg">
              <img src={logoImg} className="h-8 w-8 object-contain" alt="FLEET Logo" />
            </div>
            <span className="text-xl font-black tracking-wider text-white uppercase select-none">
              FLEET
            </span>
          </div>

          {/* Center Links (Anchors) */}
          <nav className="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-400">
            <a href="#features" className="hover:text-white transition-colors">Platform Features</a>
            <a href="#simulator" className="hover:text-white transition-colors">Interactive Demo</a>
            <a href="#stats" className="hover:text-white transition-colors">Fleet Stats</a>
            <a href="#about" className="hover:text-white transition-colors">Operations</a>
          </nav>

          {/* CTA */}
          <button
            onClick={handleCtaClick}
            className="flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/10 hover:shadow-blue-500/25 active:scale-[0.98] transition-all cursor-pointer"
          >
            {isAuthenticated ? 'Launch Console' : 'Sign In to Console'}
            <ArrowRight className="h-3.5 w-3.5" />
          </button>
        </div>
      </header>

      {/* Hero Section */}
      <section className="mx-auto max-w-7xl px-6 pt-16 pb-24 md:pt-24 md:pb-32 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
        {/* Left Hand Copy */}
        <div className="lg:col-span-6 space-y-8 text-left max-w-2xl animate-fade-in-up">
          <div className="inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-3.5 py-1.5 text-xs font-bold text-blue-400">
            <Compass className="h-4 w-4 animate-spin duration-[8000ms]" />
            Next-Generation Transport Command
          </div>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black tracking-tight leading-tight text-white uppercase">
            Logistics at the <br />
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-indigo-400 to-emerald-400">
              Speed of Code
            </span>
          </h1>
          <p className="text-base sm:text-lg text-slate-400 leading-relaxed">
            FLEET orchestrates dispatching, active tracking lanes, and carrier directories into one unified operations control center. Streamline cargo booking, monitor driver shifts, and secure manifests.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 pt-2">
            <button
              onClick={handleCtaClick}
              className="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 px-7 py-4 text-sm font-bold text-white shadow-lg shadow-blue-500/15 hover:shadow-blue-500/30 transition-all duration-150 active:scale-[0.97] cursor-pointer"
            >
              Access Control Console
              <ArrowRight className="h-4.5 w-4.5" />
            </button>
            <a
              href="#simulator"
              className="flex items-center justify-center gap-2 rounded-xl border border-slate-800 bg-slate-900/50 hover:bg-slate-900 px-7 py-4 text-sm font-bold text-slate-350 hover:text-white hover:border-slate-700 transition-all duration-150 active:scale-[0.97]"
            >
              Run Interactive Demo
            </a>
          </div>
        </div>

        {/* Right Hand Live Preview Dashboard Mock */}
        <div className="lg:col-span-6 animate-fade-in-up animation-delay-200">
          <div className="relative rounded-3xl border border-white/10 bg-[#0f1629]/65 p-6 shadow-2xl backdrop-blur-xl max-w-xl mx-auto overflow-hidden animate-float">
            {/* Ambient inner lights */}
            <div className="absolute top-0 right-0 w-36 h-36 bg-blue-500/10 rounded-full filter blur-xl"></div>
            <div className="absolute bottom-0 left-0 w-36 h-36 bg-emerald-500/10 rounded-full filter blur-xl"></div>

            {/* Simulated Header */}
            <div className="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
              <div className="flex items-center gap-2">
                <span className="h-3 w-3 rounded-full bg-red-500/80"></span>
                <span className="h-3 w-3 rounded-full bg-yellow-500/80"></span>
                <span className="h-3 w-3 rounded-full bg-green-500/80"></span>
                <span className="text-[10px] uppercase font-bold text-slate-500 ml-2 font-mono">Terminal Active // FLEET-SYS</span>
              </div>
              <div className="flex items-center gap-1.5 text-[10px] text-green-400 font-bold bg-green-950/20 border border-green-900/30 px-2.5 py-0.5 rounded-full">
                <span className="h-1.5 w-1.5 rounded-full bg-green-500 animate-ping"></span>
                LIVE NODE
              </div>
            </div>

            {/* KPI Cards inside Mock */}
            <div className="grid grid-cols-2 gap-4 mb-5">
              <div className="rounded-xl border border-white/5 bg-slate-900/40 p-3.5 text-left">
                <p className="text-[9px] uppercase font-bold text-slate-500 tracking-wider">Active Shipments</p>
                <div className="flex items-baseline gap-1.5 mt-1">
                  <span className="text-xl font-bold text-white">128</span>
                  <span className="text-[10px] text-green-400 font-semibold">+12%</span>
                </div>
              </div>
              <div className="rounded-xl border border-white/5 bg-slate-900/40 p-3.5 text-left">
                <p className="text-[9px] uppercase font-bold text-slate-500 tracking-wider">On-Time Rate</p>
                <div className="flex items-baseline gap-1.5 mt-1">
                  <span className="text-xl font-bold text-white">99.8%</span>
                  <span className="text-[10px] text-blue-400 font-semibold">Max</span>
                </div>
              </div>
            </div>

            {/* SVG Chart Preview */}
            <div className="rounded-xl border border-white/5 bg-slate-950/40 p-4 mb-5 text-left relative">
              <div className="flex items-center justify-between mb-3">
                <p className="text-[10px] uppercase font-bold text-slate-400">Regional Load Yields</p>
                <span className="text-[9px] text-slate-500 font-mono">Units: Tons</span>
              </div>
              <div className="h-28 flex items-end gap-3.5 pt-4">
                <div className="flex-1 flex flex-col items-center gap-1.5">
                  <div className="w-full bg-gradient-to-t from-blue-600/80 to-blue-500 h-16 rounded-md relative group">
                    <span className="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-900 border border-slate-800 text-white text-[8px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Accra</span>
                  </div>
                  <span className="text-[8px] text-slate-500 font-bold uppercase tracking-wider font-mono">ACC</span>
                </div>
                <div className="flex-1 flex flex-col items-center gap-1.5">
                  <div className="w-full bg-gradient-to-t from-indigo-600/80 to-indigo-500 h-24 rounded-md relative group">
                    <span className="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-900 border border-slate-800 text-white text-[8px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Kumasi</span>
                  </div>
                  <span className="text-[8px] text-slate-500 font-bold uppercase tracking-wider font-mono">KMS</span>
                </div>
                <div className="flex-1 flex flex-col items-center gap-1.5">
                  <div className="w-full bg-gradient-to-t from-emerald-600/80 to-emerald-500 h-14 rounded-md relative group">
                    <span className="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-900 border border-slate-800 text-white text-[8px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Takoradi</span>
                  </div>
                  <span className="text-[8px] text-slate-500 font-bold uppercase tracking-wider font-mono">TKD</span>
                </div>
                <div className="flex-1 flex flex-col items-center gap-1.5">
                  <div className="w-full bg-gradient-to-t from-purple-600/80 to-purple-500 h-20 rounded-md relative group">
                    <span className="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-900 border border-slate-800 text-white text-[8px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Tamale</span>
                  </div>
                  <span className="text-[8px] text-slate-500 font-bold uppercase tracking-wider font-mono">TML</span>
                </div>
              </div>
            </div>

            {/* Bottom active notification simulation */}
            <div className="flex items-center gap-3 bg-blue-950/20 border border-blue-900/30 rounded-xl p-3 text-left">
              <Activity className="h-4.5 w-4.5 text-blue-400 shrink-0 animate-pulse" />
              <div>
                <p className="text-[10px] text-blue-300 font-bold">Auto-Dispatch active in Southwest sector</p>
                <p className="text-[8px] text-slate-500 font-mono mt-0.5">3 carriers selected / ETA verified</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Bento Grid */}
      <section id="features" className="bg-[#090f1e] border-y border-slate-900 py-24 relative">
        <div className="mx-auto max-w-7xl px-6">
          {/* Header */}
          <div className="text-center max-w-2xl mx-auto space-y-4 mb-16">
            <h2 className="text-xs font-black tracking-widest text-blue-400 uppercase">Operational Cockpit</h2>
            <p className="text-3xl font-black text-white uppercase sm:text-4xl">Platform Features Built to Deliver</p>
            <p className="text-sm text-slate-400">
              Our Transport Management System replaces spreadsheets with real-time state machines, giving your operators instantaneous command of shipping manifests.
            </p>
          </div>

          {/* Bento Layout */}
          <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
            {/* Card 1: Interactive Route Mapping (Large Column) */}
            <div className="md:col-span-8 rounded-3xl border border-white/5 bg-[#0e1526]/50 p-6 flex flex-col justify-between hover:border-blue-500/30 transition-all duration-300 group">
              <div className="text-left space-y-2 mb-6">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-950/50 border border-blue-800/40 text-blue-400">
                  <Globe className="h-5 w-5" />
                </div>
                <h3 className="text-lg font-bold text-white">Regional Tracking Map</h3>
                <p className="text-xs text-slate-400 max-w-md">
                  Logistics routes map out dynamically inside an interactive viewport showing city pins, direct truck lanes, and real-time status pulses.
                </p>
              </div>

              {/* Minimal SVG map mockup */}
              <div className="bg-[#070b13] rounded-2xl border border-slate-900 p-4 aspect-[21/9] flex items-center justify-center overflow-hidden relative">
                <div className="absolute inset-0 bg-[linear-gradient(to_right,#161f38_1px,transparent_1px),linear-gradient(to_bottom,#161f38_1px,transparent_1px)] bg-[size:20px_20px] opacity-10"></div>
                <svg className="w-full h-full max-w-[400px]" viewBox="0 0 200 100">
                  {/* Arc */}
                  <path d="M 30,70 Q 100,20 170,70" className="fill-none stroke-blue-500/40" strokeWidth="1" />
                  <path d="M 30,70 Q 100,20 170,70" className="fill-none stroke-blue-400 stroke-dasharray-[4,4] animate-[dash_20s_linear_infinite]" strokeWidth="1" />
                  
                  {/* Origin */}
                  <circle cx="30" cy="70" r="3" className="fill-slate-500" />
                  <text x="15" y="85" className="fill-slate-500 text-[6px] font-mono">ACC</text>
                  
                  {/* Destination */}
                  <circle cx="170" cy="70" r="3" className="fill-emerald-500" />
                  <text x="160" y="85" className="fill-emerald-500 text-[6px] font-mono font-bold">TML</text>
                  
                  {/* Truck dot */}
                  <circle cx="100" cy="45" r="4.5" className="fill-blue-500/25 animate-ping" />
                  <circle cx="100" cy="45" r="2.5" className="fill-blue-600 stroke-slate-950" strokeWidth="1" />
                </svg>
              </div>
            </div>

            {/* Card 2: AI Dispatch Rules (Small Column) */}
            <div className="md:col-span-4 rounded-3xl border border-white/5 bg-[#0e1526]/50 p-6 flex flex-col justify-between hover:border-blue-500/30 transition-all duration-300">
              <div className="text-left space-y-2 mb-6">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-950/50 border border-purple-800/40 text-purple-400">
                  <Settings className="h-5 w-5" />
                </div>
                <h3 className="text-lg font-bold text-white">Dynamic Dispatch</h3>
                <p className="text-xs text-slate-400">
                  Instantly verify weights, route clearances, and target ETAs inside custom input controls with automatic validation locks.
                </p>
              </div>

              {/* Code command prompt mockup */}
              <div className="bg-[#070b13] rounded-2xl border border-slate-900 p-4 font-mono text-[9px] text-purple-300 text-left space-y-1.5">
                <p className="text-slate-550">&gt; fleet dispatch --cargo-id ORD-84</p>
                <p className="text-slate-300">Evaluating Accra → Tamale...</p>
                <p className="text-green-400">✓ Truck capacity matches weight</p>
                <p className="text-green-400">✓ Fleet driver off-duty limit verified</p>
                <p className="text-blue-400">Status: Dispatched via Swift Log</p>
              </div>
            </div>

            {/* Card 3: Carrier Manager (Small Column) */}
            <div className="md:col-span-4 rounded-3xl border border-white/5 bg-[#0e1526]/50 p-6 flex flex-col justify-between hover:border-blue-500/30 transition-all duration-300">
              <div className="text-left space-y-2 mb-6">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-950/50 border border-amber-800/40 text-amber-400">
                  <Layers className="h-5 w-5" />
                </div>
                <h3 className="text-lg font-bold text-white">Carrier Bookings</h3>
                <p className="text-xs text-slate-400">
                  Integrate seamlessly with regional carriers. Support for Apex Freight, Swift Logistics, and BlueSky bookings.
                </p>
              </div>

              {/* Mock list */}
              <div className="space-y-2">
                <div className="flex items-center justify-between rounded-xl bg-[#070b13] border border-slate-900 p-2.5 text-xs">
                  <span className="font-bold text-white">Swift Logistics</span>
                  <span className="text-[9px] font-bold text-green-400 bg-green-950/20 px-2 py-0.5 rounded border border-green-900/30">Contractor</span>
                </div>
                <div className="flex items-center justify-between rounded-xl bg-[#070b13] border border-slate-900 p-2.5 text-xs">
                  <span className="font-bold text-white">Apex Freight</span>
                  <span className="text-[9px] font-bold text-blue-400 bg-blue-950/20 px-2 py-0.5 rounded border border-blue-900/30">Primary</span>
                </div>
              </div>
            </div>

            {/* Card 4: Operations Logs (Large Column) */}
            <div className="md:col-span-8 rounded-3xl border border-white/5 bg-[#0e1526]/50 p-6 flex flex-col justify-between hover:border-blue-500/30 transition-all duration-300">
              <div className="text-left space-y-2 mb-6">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-950/50 border border-emerald-800/40 text-emerald-400">
                  <Users className="h-5 w-5" />
                </div>
                <h3 className="text-lg font-bold text-white">Driver Duty Cockpit</h3>
                <p className="text-xs text-slate-400 max-w-md">
                  Keep operators running safely. Direct telephone hotkeys, duty status markers (Active, Resting, Maintenance), and truck models integrated in a grid index.
                </p>
              </div>

              {/* Driver Grid preview mockup */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="rounded-2xl border border-slate-900 bg-[#070b13] p-3 flex items-center gap-3">
                  <div className="h-8 w-8 rounded-full bg-slate-800 overflow-hidden flex items-center justify-center text-[10px] font-bold text-white">JD</div>
                  <div className="text-left">
                    <p className="text-xs font-bold text-white">John Doe</p>
                    <p className="text-[8px] text-slate-500">Active • Swift Semi #1209</p>
                  </div>
                </div>
                <div className="rounded-2xl border border-slate-900 bg-[#070b13] p-3 flex items-center gap-3">
                  <div className="h-8 w-8 rounded-full bg-slate-800 overflow-hidden flex items-center justify-center text-[10px] font-bold text-white">AS</div>
                  <div className="text-left">
                    <p className="text-xs font-bold text-white">Alice Smith</p>
                    <p className="text-[8px] text-slate-500">Resting • Apex Volvo #8410</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Interactive Simulator Section */}
      <section id="simulator" className="mx-auto max-w-7xl px-6 py-24">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
          {/* Simulation console left */}
          <div className="lg:col-span-5 space-y-6 text-left">
            <div className="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400">
              <Terminal className="h-3.5 w-3.5" />
              Live Interactive Sandbox
            </div>
            <h2 className="text-3xl font-black text-white uppercase leading-tight sm:text-4xl">
              Take the Wheel of <br />
              <span className="text-blue-400">Dispatch Automation</span>
            </h2>
            <p className="text-slate-400 text-sm leading-relaxed">
              FLEET operates as a reactive state machine. Click the trigger below to dispatch a mock shipment manifest. Watch the operations queue step through route lanes, driver check-ins, and manifest signing.
            </p>
            <div className="pt-2">
              <button
                onClick={runSimulator}
                disabled={simRunning}
                className="w-full sm:w-auto flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 px-6 py-3.5 text-xs font-bold text-white shadow-lg shadow-blue-500/10 hover:shadow-blue-500/25 active:scale-[0.98] transition-all cursor-pointer"
              >
                {simRunning ? 'Dispatching Manifest...' : 'Trigger Dispatch Simulator'}
                <Truck className="h-4.5 w-4.5 shrink-0" />
              </button>
            </div>
          </div>

          {/* Interactive UI Mockup Right */}
          <div className="lg:col-span-7">
            <div className="rounded-3xl border border-white/10 bg-[#0e1526]/55 p-6 shadow-xl backdrop-blur-xl space-y-6 text-left relative overflow-hidden">
              <div className="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full filter blur-xl"></div>
              
              {/* Simulator Header */}
              <div className="flex items-center justify-between border-b border-slate-900 pb-4">
                <span className="text-xs font-bold text-white font-mono flex items-center gap-2">
                  <Terminal className="h-4 w-4 text-blue-400" />
                  DISPATCH_TERMINAL_NODE // SH-1092
                </span>
                <span className="text-[10px] text-slate-500 font-mono">STATE: {simStep === 0 ? 'STANDBY' : simStep === 3 ? 'COMPLETED' : 'IN_PROGRESS'}</span>
              </div>

              {/* Progress visual bar */}
              <div className="space-y-2">
                <div className="flex justify-between text-[10px] text-slate-400 font-bold uppercase">
                  <span>Accra (Origin)</span>
                  <span>Tamale (Destination)</span>
                </div>
                <div className="h-3 w-full bg-slate-950 border border-slate-900 rounded-full overflow-hidden p-0.5">
                  <div 
                    className="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-1000 ease-out" 
                    style={{ 
                      width: `${simStep === 0 ? 0 : simStep === 1 ? 25 : simStep === 2 ? 65 : 100}%` 
                    }}
                  ></div>
                </div>
                
                {/* SVG Route Line Mock */}
                <div className="bg-slate-950 rounded-xl border border-slate-900 p-4 aspect-[16/5] flex items-center justify-center relative">
                  <div className="absolute inset-0 bg-[linear-gradient(to_right,#161f38_1px,transparent_1px)] bg-[size:30px] opacity-10"></div>
                  
                  {/* SVG lane representation */}
                  <svg className="w-full h-full" viewBox="0 0 300 80">
                    <line x1="40" y1="40" x2="260" y2="40" className="stroke-slate-900" strokeWidth="2" />
                    <line x1="40" y1="40" x2="260" y2="40" className="stroke-slate-800" strokeWidth="1" strokeDasharray="4 4" />
                    
                    {/* Animated routed progress */}
                    {simStep > 0 && (
                      <line 
                        x1="40" 
                        y1="40" 
                        x2={simStep === 1 ? 95 : simStep === 2 ? 183 : 260} 
                        y2="40" 
                        className="stroke-blue-500 transition-all duration-1000 ease-out" 
                        strokeWidth="2.5" 
                      />
                    )}

                    {/* Nodes */}
                    <circle cx="40" cy="40" r="6" className={`transition-colors duration-500 ${simStep >= 1 ? 'fill-blue-500 stroke-blue-900/40' : 'fill-slate-800 stroke-slate-950'}`} strokeWidth="2.5" />
                    <circle cx="150" cy="40" r="6" className={`transition-colors duration-500 ${simStep >= 2 ? 'fill-blue-600 stroke-blue-900/40' : 'fill-slate-800 stroke-slate-950'}`} strokeWidth="2.5" />
                    <circle cx="260" cy="40" r="6" className={`transition-colors duration-500 ${simStep >= 3 ? 'fill-emerald-500 stroke-emerald-950/40' : 'fill-slate-800 stroke-slate-950'}`} strokeWidth="2.5" />

                    {/* Truck icon overlay */}
                    {simStep > 0 && (
                      <g 
                        className="transition-all duration-1000 ease-out" 
                        transform={`translate(${simStep === 1 ? 83 : simStep === 2 ? 171 : 248}, 16)`}
                      >
                        <rect width="24" height="12" rx="2" className="fill-blue-500 stroke-slate-950" strokeWidth="1" />
                        <circle cx="6" cy="12" r="2.5" className="fill-slate-900" />
                        <circle cx="18" cy="12" r="2.5" className="fill-slate-900" />
                        <rect x="18" y="3" width="5" height="6" className="fill-blue-200" />
                      </g>
                    )}
                  </svg>
                </div>
              </div>

              {/* Terminal Operations Log Output */}
              <div className="space-y-2">
                <p className="text-[10px] font-bold uppercase text-slate-400">Terminal Operations Logs</p>
                <div className="bg-[#070b13] rounded-2xl border border-slate-900 p-4 h-32 overflow-y-auto font-mono text-[10px] text-slate-400 space-y-1.5 scrollbar-thin">
                  {simLog.map((logLine, idx) => {
                    let logColor = 'text-slate-400'
                    if (logLine.includes('[DISPATCH]')) logColor = 'text-amber-400'
                    if (logLine.includes('[ROUTE]')) logColor = 'text-purple-400'
                    if (logLine.includes('[TRANSIT]')) logColor = 'text-blue-400'
                    if (logLine.includes('[DELIVERED]')) logColor = 'text-emerald-400'
                    if (logLine.includes('[SYSTEM]')) logColor = 'text-slate-500'

                    return (
                      <div key={idx} className={`${logColor} leading-relaxed`}>
                        {logLine}
                      </div>
                    )
                  })}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Trust Statistics Section */}
      <section id="stats" className="bg-[#090f1e] border-y border-slate-900 py-16 relative">
        <div className="mx-auto max-w-7xl px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div className="space-y-1 animate-in fade-in zoom-in-95">
            <span className="block text-3xl sm:text-4xl font-black text-white">99.8%</span>
            <span className="block text-[10px] uppercase font-bold text-blue-400 tracking-widest">On-Time rate</span>
            <p className="text-[10px] text-slate-500">Industry threshold average: 92.5%</p>
          </div>
          <div className="space-y-1 animate-in fade-in zoom-in-95">
            <span className="block text-3xl sm:text-4xl font-black text-white">GH₵1.2B</span>
            <span className="block text-[10px] uppercase font-bold text-indigo-400 tracking-widest">Cargo Monitored</span>
            <p className="text-[10px] text-slate-500">Across 3 regional hubs</p>
          </div>
          <div className="space-y-1 animate-in fade-in zoom-in-95">
            <span className="block text-3xl sm:text-4xl font-black text-white">&lt; 12ms</span>
            <span className="block text-[10px] uppercase font-bold text-purple-400 tracking-widest">Route Latency</span>
            <p className="text-[10px] text-slate-500">AI load lane generation</p>
          </div>
          <div className="space-y-1 animate-in fade-in zoom-in-95">
            <span className="block text-3xl sm:text-4xl font-black text-white">300+</span>
            <span className="block text-[10px] uppercase font-bold text-emerald-400 tracking-widest">Active Crews</span>
            <p className="text-[10px] text-slate-500">Certified Class-A operators</p>
          </div>
        </div>
      </section>

      {/* Trust Testimonials Section */}
      <section className="mx-auto max-w-7xl px-6 py-24 text-center">
        <div className="max-w-2xl mx-auto space-y-4 mb-16">
          <h2 className="text-xs font-black tracking-widest text-blue-400 uppercase">Operational Reviews</h2>
          <p className="text-3xl font-black text-white uppercase sm:text-4xl">Trusted by Dispatch Command Towers</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* Card 1 */}
          <div className="rounded-3xl border border-white/5 bg-[#0e1526]/40 p-6 space-y-4 text-left backdrop-blur-sm">
            <div className="flex items-center gap-1.5 text-yellow-500">
              <ShieldCheck className="h-4.5 w-4.5 fill-yellow-500/20" />
              <span className="text-xs font-bold text-slate-300">Verified Dispatcher Review</span>
            </div>
            <p className="text-xs text-slate-350 leading-relaxed font-medium">
              "We migrated all active bookings to FLEET. The reactive layout and the custom USA lane rendering let our dispatch desk locate driver positions in seconds without page loads. The latency on cargo creation is virtually non-existent."
            </p>
            <div className="border-t border-slate-900 pt-3 flex items-center gap-3">
              <div className="h-8 w-8 rounded-full bg-slate-850 flex items-center justify-center font-bold text-white text-[10px]">MC</div>
              <div className="text-[10px]">
                <p className="font-bold text-white">Marcus Vance</p>
                <p className="text-slate-500">Logistics Director, Apex Freight</p>
              </div>
            </div>
          </div>

          {/* Card 2 */}
          <div className="rounded-3xl border border-white/5 bg-[#0e1526]/40 p-6 space-y-4 text-left backdrop-blur-sm">
            <div className="flex items-center gap-1.5 text-yellow-500">
              <ShieldCheck className="h-4.5 w-4.5 fill-yellow-500/20" />
              <span className="text-xs font-bold text-slate-300">Verified Dispatcher Review</span>
            </div>
            <p className="text-xs text-slate-350 leading-relaxed font-medium">
              "The driver directory card system with direct telephone call capabilities has drastically reduced operator response delays. Adding class-based dark mode makes late-night shifts comfortable for our dispatchers."
            </p>
            <div className="border-t border-slate-900 pt-3 flex items-center gap-3">
              <div className="h-8 w-8 rounded-full bg-slate-850 flex items-center justify-center font-bold text-white text-[10px]">RH</div>
              <div className="text-[10px]">
                <p className="font-bold text-white">Rebecca Hayes</p>
                <p className="text-slate-500">Operations Lead, Swift Logistics</p>
              </div>
            </div>
          </div>

          {/* Card 3 */}
          <div className="rounded-3xl border border-white/5 bg-[#0e1526]/40 p-6 space-y-4 text-left backdrop-blur-sm">
            <div className="flex items-center gap-1.5 text-yellow-500">
              <ShieldCheck className="h-4.5 w-4.5 fill-yellow-500/20" />
              <span className="text-xs font-bold text-slate-300">Verified Dispatcher Review</span>
            </div>
            <p className="text-xs text-slate-350 leading-relaxed font-medium">
              "Local storage caching allows our booking list to persist across logins. We can book loads, track truck routing, and update status manifests in our browser without setting up database clusters."
            </p>
            <div className="border-t border-slate-900 pt-3 flex items-center gap-3">
              <div className="h-8 w-8 rounded-full bg-slate-850 flex items-center justify-center font-bold text-white text-[10px]">TL</div>
              <div className="text-[10px]">
                <p className="font-bold text-white">Timothy Lake</p>
                <p className="text-slate-500">Fleet Dispatcher, Titan Cargo</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Footer operations section */}
      <footer id="about" className="bg-[#05070c] border-t border-slate-950 py-16">
        <div className="mx-auto max-w-7xl px-6 grid grid-cols-1 md:grid-cols-12 gap-12 items-center">
          {/* Logo and info */}
          <div className="md:col-span-5 space-y-4 text-left">
            <div className="flex items-center gap-3">
              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 p-1 border border-white/10">
                <img src={logoImg} className="h-6 w-6 object-contain" alt="FLEET logo" />
              </div>
              <span className="text-md font-bold tracking-wider text-white">FLEET CONTROL</span>
            </div>
            <p className="text-[11px] text-slate-500 leading-relaxed max-w-xs">
              Secure, serverless logistics tracking and command panels designed for carrier agencies and dispatcher hubs. 
            </p>
            <p className="text-[10px] text-slate-600 font-mono">
              &copy; {new Date().getFullYear()} FLEET Inc. All operational rights reserved.
            </p>
          </div>

          {/* Contact or quick Links */}
          <div className="md:col-span-3 text-left space-y-3">
            <p className="text-xs font-bold text-white uppercase tracking-wider">Operation Nodes</p>
            <div className="space-y-1.5 text-[11px] text-slate-500 font-medium">
              <p className="flex items-center gap-1.5"><MapPin className="h-3.5 w-3.5" /> Accra, Ghana Command Tower</p>
              <p className="flex items-center gap-1.5"><PhoneCall className="h-3.5 w-3.5" /> +1 (800) 555-FLEET</p>
            </div>
          </div>

          {/* Email dispatch signup */}
          <div className="md:col-span-4 text-left space-y-3">
            <p className="text-xs font-bold text-white uppercase tracking-wider">Dispatch System Updates</p>
            <div className="flex gap-2">
              <input
                type="email"
                placeholder="dispatcher@agency.com"
                className="flex-1 rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-350 focus:border-blue-500 focus:outline-none"
              />
              <button className="rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2 text-xs font-bold text-white cursor-pointer">
                Subscribe
              </button>
            </div>
          </div>
        </div>
      </footer>
    </div>
  )
}
