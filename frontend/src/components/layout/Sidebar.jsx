import { NavLink, useParams } from 'react-router-dom'
import { LayoutDashboard, FileText, Palette, BookOpen, BookMarked, TextQuote, ShieldAlert, History, FileBarChart, Layers } from 'lucide-react'
import { useAuthStore } from '../../stores/authStore'

const mainNav = [
  { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/documents', label: 'Documents', icon: FileText },
  { to: '/batches', label: 'Batches', icon: Layers },
  { to: '/style-profiles', label: 'Style Profiles', icon: Palette },
]

export default function Sidebar() {
  const user = useAuthStore((s) => s.user)
  const { id: documentId } = useParams()

  return (
    <aside className="fixed inset-y-0 left-0 w-64 bg-white border-r border-slate-200 flex flex-col">
      <div className="h-16 flex items-center px-6 border-b border-slate-200">
        <span className="text-xl font-bold text-blue-600">DocFormat</span>
      </div>
      <nav className="flex-1 px-3 py-4 space-y-1">
        {mainNav.map(({ to, label, icon: Icon }) => (
          <NavLink
            key={to}
            to={to}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition ${
                isActive
                  ? 'bg-blue-50 text-blue-700'
                  : 'text-slate-600 hover:bg-slate-100'
              }`
            }
          >
            <Icon size={18} />
            {label}
          </NavLink>
        ))}

        {documentId && (
          <>
            <div className="pt-4 pb-2">
              <p className="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Document Analysis</p>
            </div>
            <NavLink
              to={`/documents/${documentId}/citations`}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition ${
                  isActive
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-100'
                }`
              }
            >
              <BookOpen size={18} />
              Citations
            </NavLink>
            <NavLink
              to={`/documents/${documentId}/bibliography`}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition ${
                  isActive
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-100'
                }`
              }
            >
              <BookMarked size={18} />
              Bibliography
            </NavLink>
            <NavLink
              to={`/documents/${documentId}/abbreviations`}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition ${
                  isActive
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-100'
                }`
              }
            >
              <TextQuote size={18} />
              Abbreviations
            </NavLink>
            <NavLink
              to={`/documents/${documentId}/issues`}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition ${
                  isActive
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-100'
                }`
              }
            >
              <ShieldAlert size={18} />
              Issues
            </NavLink>
            <NavLink
              to={`/documents/${documentId}/report`}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition ${
                  isActive
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-100'
                }`
              }
            >
              <FileBarChart size={18} />
              Report
            </NavLink>
            <NavLink
              to={`/documents/${documentId}`}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition ${
                  isActive
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-100'
                }`
              }
            >
              <History size={18} />
              Workspace
            </NavLink>
          </>
        )}
      </nav>
      {user && (
        <div className="px-3 py-4 border-t border-slate-200">
          <p className="text-sm font-medium text-slate-900 truncate">{user.name}</p>
          <p className="text-xs text-slate-500 truncate">{user.email}</p>
        </div>
      )}
    </aside>
  )
}
