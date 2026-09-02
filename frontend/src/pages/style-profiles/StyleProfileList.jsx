import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { Plus, Upload, Trash2, Edit, Shield, Copy } from 'lucide-react'
import { listStyleProfiles, deleteStyleProfile, exportStyleProfile } from '../../api/styleProfiles'

const TYPE_LABELS = {
  university: 'University',
  thesis: 'Thesis',
  report: 'Report',
  article: 'Article',
  custom: 'Custom',
}

const TYPE_COLORS = {
  university: 'bg-blue-100 text-blue-800',
  thesis: 'bg-purple-100 text-purple-800',
  report: 'bg-amber-100 text-amber-800',
  article: 'bg-green-100 text-green-800',
  custom: 'bg-slate-100 text-slate-800',
}

export default function StyleProfileList() {
  const [profiles, setProfiles] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    loadProfiles()
  }, [])

  const loadProfiles = async () => {
    setLoading(true)
    try {
      const data = await listStyleProfiles()
      setProfiles(data.data || [])
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load profiles')
    } finally {
      setLoading(false)
    }
  }

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this profile?')) return
    try {
      await deleteStyleProfile(id)
      setProfiles((prev) => prev.filter((p) => p.id !== id))
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to delete profile')
    }
  }

  const handleExport = async (id, name) => {
    try {
      const data = await exportStyleProfile(id)
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${name.replace(/\s+/g, '-').toLowerCase()}.json`
      a.click()
      URL.revokeObjectURL(url)
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to export profile')
    }
  }

  if (loading) {
    return (
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="h-8 w-48 bg-slate-100 rounded animate-pulse" />
          <div className="h-10 w-32 bg-slate-100 rounded animate-pulse" />
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-48 bg-slate-100 rounded-lg animate-pulse" />
          ))}
        </div>
      </div>
    )
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">Style Profiles</h1>
          <p className="mt-1 text-sm text-slate-500">Define formatting rules for your documents</p>
        </div>
        <div className="flex gap-2">
          <Link
            to="/style-profiles/new"
            className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition"
          >
            <Plus size={16} />
            Create Profile
          </Link>
        </div>
      </div>

      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
          {error}
        </div>
      )}

      {profiles.length === 0 ? (
        <div className="text-center py-12 bg-white rounded-lg border border-slate-200">
          <Shield className="mx-auto h-12 w-12 text-slate-300" />
          <h3 className="mt-2 text-sm font-medium text-slate-900">No style profiles</h3>
          <p className="mt-1 text-sm text-slate-500">Get started by creating a new profile.</p>
          <div className="mt-6">
            <Link
              to="/style-profiles/new"
              className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
            >
              <Plus size={16} />
              Create Profile
            </Link>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {profiles.map((profile) => (
            <div
              key={profile.id}
              className="bg-white rounded-lg border border-slate-200 p-5 hover:shadow-md transition"
            >
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1 min-w-0">
                  <h3 className="text-base font-semibold text-slate-900 truncate">{profile.name}</h3>
                  {profile.description && (
                    <p className="mt-1 text-sm text-slate-500 line-clamp-2">{profile.description}</p>
                  )}
                </div>
                {profile.is_system && (
                  <span className="ml-2 inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 rounded">
                    <Shield size={12} />
                    System
                  </span>
                )}
              </div>

              <div className="flex items-center gap-2 mb-4">
                <span className={`inline-flex items-center px-2 py-0.5 text-xs font-medium rounded ${TYPE_COLORS[profile.type] || TYPE_COLORS.custom}`}>
                  {TYPE_LABELS[profile.type] || profile.type}
                </span>
                <span className="text-xs text-slate-400">v{profile.version}</span>
                <span className="text-xs text-slate-400">{profile.language}</span>
              </div>

              {profile.owner && (
                <p className="text-xs text-slate-400 mb-3">by {profile.owner.name}</p>
              )}

              <div className="flex items-center gap-1 pt-3 border-t border-slate-100">
                <Link
                  to={`/style-profiles/${profile.id}/edit`}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100 rounded-md transition"
                >
                  <Edit size={14} />
                  Edit
                </Link>
                <button
                  onClick={() => handleExport(profile.id, profile.name)}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100 rounded-md transition"
                >
                  <Copy size={14} />
                  Export
                </button>
                {!profile.is_system && (
                  <button
                    onClick={() => handleDelete(profile.id)}
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-md transition ml-auto"
                  >
                    <Trash2 size={14} />
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
