import { Link } from 'react-router-dom'
import { FileText } from 'lucide-react'

export default function AuthLayout({ children, title, subtitle }) {
  return (
    <div className="min-h-[100dvh] flex flex-col bg-slate-50">
      <header className="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-5">
        <Link to="/" className="flex items-center gap-2">
          <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white">
            <FileText size={18} />
          </span>
          <span className="text-lg font-semibold tracking-tight text-slate-900">DocFormat</span>
        </Link>
        <Link to="/" className="text-sm text-slate-500 hover:text-slate-900 transition">Back to home</Link>
      </header>

      <main className="flex flex-1 items-center justify-center px-6 py-10">
        <div className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
          <h1 className="text-2xl font-semibold text-slate-900">{title}</h1>
          {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
          <div className="mt-6">{children}</div>
        </div>
      </main>
    </div>
  )
}
