import { useState, useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import { Mail, Lock, ShieldCheck, ArrowRight, Loader2, Eye, EyeOff } from 'lucide-react'
import logoImg from '../assets/Logo1.png'
import bgImg from '../assets/image1.jpg'

export default function Login() {
  const navigate = useNavigate()
  
  // Input references for focus management
  const emailInputRef = useRef<HTMLInputElement>(null)
  const passwordInputRef = useRef<HTMLInputElement>(null)

  // Form states
  const [email, setEmail] = useState('dispatcher@fleet.com')
  const [password, setPassword] = useState('fleet123')
  const [showPassword, setShowPassword] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [rememberMe, setRememberMe] = useState(true)

  // Redirect if already logged in
  useEffect(() => {
    const isAuthenticated = localStorage.getItem('tms_authenticated') === 'true'
    if (isAuthenticated) {
      navigate('/app', { replace: true })
    }
  }, [navigate])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    
    if (!email) {
      setError('Please enter your email address.')
      emailInputRef.current?.focus()
      return
    }
    
    if (!password) {
      setError('Please enter your password.')
      passwordInputRef.current?.focus()
      return
    }

    setIsSubmitting(true)

    // Simulate network authentication call with micro-interaction timer
    setTimeout(() => {
      if (email === 'dispatcher@fleet.com' && password === 'fleet123') {
        localStorage.setItem('tms_authenticated', 'true')
        localStorage.setItem('tms_user_email', email)
        navigate('/app', { replace: true })
      } else {
        setError('Invalid email or password. Hint: dispatcher@fleet.com / fleet123')
        setIsSubmitting(false)
        // Focus back to password on fail
        passwordInputRef.current?.focus()
      }
    }, 1200)
  }

  return (
    <div 
      className="flex min-h-screen w-screen items-center justify-center bg-[#090d16] px-4 py-12 relative overflow-hidden bg-cover bg-center bg-no-repeat"
      style={{ 
        backgroundImage: `linear-gradient(to bottom, rgba(9, 13, 22, 0.5), rgba(9, 13, 22, 0.5)), url(${bgImg})`
      }}
    >
      {/* Background ambient blobs with fluid infinite custom animation keyframes */}
      <div className="absolute top-[-10%] -left-10 w-96 h-96 bg-blue-500/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
      <div className="absolute -bottom-10 right-10 w-96 h-96 bg-emerald-500/10 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
      <div className="absolute top-1/3 right-1/4 w-80 h-80 bg-purple-500/5 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-4000"></div>

      {/* Grid line layer overlay for logistics vibe */}
      <div className="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-20 pointer-events-none"></div>

      {/* Centered Glassmorphic login card with slide-in-up animation */}
      <div className="w-full max-w-md space-y-8 bg-slate-900/60 border border-white/10 p-8 rounded-3xl shadow-2xl backdrop-blur-xl relative z-10 opacity-0 animate-fade-in-up">
        
        {/* Brand Header with Float animation */}
        <div className="flex flex-col items-center justify-center text-center">
          <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950/80 p-2.5 border border-white/15 mb-4 shadow-xl animate-float">
            <img src={logoImg} className="h-12 w-12 object-contain" alt="FLEET Logo" />
          </div>
          <h2 className="text-2xl font-black tracking-wider text-white uppercase select-none">
            FLEET <span className="text-blue-500 font-medium">Control</span>
          </h2>
          <p className="mt-1 text-xs text-slate-450 font-medium">
            Transport Management & Dispatch Operations
          </p>
        </div>

        {/* Info Note - Staggered delay */}
        <div className="rounded-2xl bg-blue-950/30 border border-blue-900/30 p-3 text-center opacity-0 animate-fade-in-up animation-delay-100">
          <p className="text-[10px] text-blue-300 leading-normal font-semibold flex items-center justify-center gap-1.5">
            <ShieldCheck className="h-3.5 w-3.5 shrink-0 text-blue-400" />
            Default account: <span className="text-white font-bold underline">dispatcher@fleet.com</span> / <span className="text-white font-bold underline">fleet123</span>
          </p>
        </div>

        {/* Form Error */}
        {error && (
          <div className="rounded-xl bg-rose-950/40 border border-rose-900/30 p-3 text-xs text-rose-300 font-medium text-center animate-bounce">
            {error}
          </div>
        )}

        {/* Login Form */}
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Email input - Staggered delay */}
          <div className="opacity-0 animate-fade-in-up animation-delay-100">
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
              Work Email
            </label>
            <div className="relative group">
              <Mail className="absolute top-1/2 left-3 h-4.5 w-4.5 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" />
              <input
                ref={emailInputRef}
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="you@fleet.com"
                className="w-full rounded-xl border border-slate-700 bg-slate-950/40 py-2.5 pr-4 pl-10 text-sm text-slate-200 transition-all focus:border-blue-500/80 focus:bg-slate-950/80 focus:ring-1 focus:ring-blue-500/30 focus:outline-none"
              />
            </div>
          </div>

          {/* Password input - Staggered delay */}
          <div className="opacity-0 animate-fade-in-up animation-delay-200">
            <div className="flex items-center justify-between mb-1.5">
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                Password
              </label>
              <a href="#forgot" className="text-[10px] font-bold text-blue-400 hover:underline">
                Forgot key?
              </a>
            </div>
            <div className="relative group">
              <Lock className="absolute top-1/2 left-3 h-4.5 w-4.5 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" />
              <input
                ref={passwordInputRef}
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full rounded-xl border border-slate-700 bg-slate-950/40 py-2.5 pr-10 pl-10 text-sm text-slate-200 transition-all focus:border-blue-500/80 focus:bg-slate-950/80 focus:ring-1 focus:ring-blue-500/30 focus:outline-none"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute top-1/2 right-3 -translate-y-1/2 p-1 rounded hover:bg-slate-800 text-slate-500 hover:text-slate-350 transition-colors cursor-pointer"
                title={showPassword ? 'Hide password' : 'Show password'}
              >
                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
            </div>
          </div>

          {/* Remember me - Staggered delay */}
          <div className="flex items-center opacity-0 animate-fade-in-up animation-delay-300">
            <input
              id="remember-me"
              type="checkbox"
              checked={rememberMe}
              onChange={(e) => setRememberMe(e.target.checked)}
              className="h-4 w-4 rounded border-slate-700 bg-slate-950 text-blue-600 focus:ring-blue-500 focus:ring-offset-0 focus:outline-none"
            />
            <label htmlFor="remember-me" className="ml-2 block text-xs font-semibold text-slate-400 select-none cursor-pointer">
              Remember dispatcher terminal
            </label>
          </div>

          {/* Submit button - Staggered delay */}
          <div className="opacity-0 animate-fade-in-up animation-delay-300 pt-2">
            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/10 hover:shadow-blue-500/20 active:scale-[0.97] transition-all duration-150 disabled:opacity-50 cursor-pointer"
            >
              {isSubmitting ? (
                <>
                  <Loader2 className="h-4.5 w-4.5 animate-spin" />
                  Accessing Control Tower...
                </>
              ) : (
                <>
                  Sign In to Console
                  <ArrowRight className="h-4.5 w-4.5 transition-transform group-hover:translate-x-1" />
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
