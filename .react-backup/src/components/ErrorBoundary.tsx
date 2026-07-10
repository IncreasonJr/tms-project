import { Component } from 'react'
import type { ErrorInfo, ReactNode } from 'react'
import { AlertTriangle, RefreshCw } from 'lucide-react'

interface Props {
  children?: ReactNode
}

interface State {
  hasError: boolean
  error: Error | null
}

export class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false,
    error: null
  }

  public static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error }
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('Uncaught error caught by boundary:', error, errorInfo)
  }

  private handleReload = () => {
    window.location.reload()
  }

  public render() {
    if (this.state.hasError) {
      return (
        <div className="flex min-h-[400px] flex-col items-center justify-center rounded-2xl border border-red-100 bg-red-55/30 p-8 text-center backdrop-blur-sm">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 mb-4">
            <AlertTriangle className="h-6 w-6" />
          </div>
          <h2 className="text-lg font-bold text-slate-900 mb-2">View Rendering Error</h2>
          <p className="text-sm text-slate-500 max-w-md mb-6">
            An unexpected error occurred while loading this section of the system.
          </p>
          <button
            onClick={this.handleReload}
            className="flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-all duration-200"
          >
            <RefreshCw className="h-4 w-4" />
            Retry View
          </button>
        </div>
      )
    }

    return this.props.children
  }
}
