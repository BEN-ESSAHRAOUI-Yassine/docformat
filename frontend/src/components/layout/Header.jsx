import { useNavigate } from 'react-router-dom'
import { LogOut, User } from 'lucide-react'
import { useAuthStore } from '../../stores/authStore'
import { logout as apiLogout } from '../../api/auth'

export default function Header() {
  const { user, logout } = useAuthStore()
  const navigate = useNavigate()

  const handleLogout = async () => {
    try { await apiLogout() } catch {}
    logout()
    navigate('/login')
  }

  return (
    <header className="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
      <div />
      <div className="flex items-center gap-4">
        <div className="flex items-center gap-2 text-sm text-slate-600">
          <User size={16} />
          <span>{user?.name}</span>
        </div>
        <button
          onClick={handleLogout}
          className="flex items-center gap-2 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100 rounded-md transition"
        >
          <LogOut size={16} />
          Logout
        </button>
      </div>
    </header>
  )
}
