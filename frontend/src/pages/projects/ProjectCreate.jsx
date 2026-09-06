import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { createProject } from '../../api/projects'

export default function ProjectCreate() {
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!name.trim()) {
      setError('Project name is required.')
      return
    }
    setSaving(true)
    setError(null)
    try {
      const data = await createProject({ name, description })
      const projectId = data?.data?.id ?? data?.id
      navigate(`/documents`)
    } catch (err) {
      setError(err.response?.data?.message || err.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="max-w-xl">
      <Link to="/documents" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900">
        <ArrowLeft size={16} /> Back
      </Link>
      <h1 className="mt-4 text-2xl font-semibold text-slate-900">New Project</h1>
      <p className="mt-1 text-slate-500">Projects group your documents and batches.</p>

      <form onSubmit={handleSubmit} className="mt-8 rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        <div>
          <label className="text-sm font-medium text-slate-700">Name</label>
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="e.g. Master's Thesis"
          />
        </div>
        <div>
          <label className="text-sm font-medium text-slate-700">Description</label>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            className="mt-1 w-full min-h-[80px] rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Optional description"
          />
        </div>
        {error && <p className="text-sm text-red-600">{error}</p>}
        <button
          type="submit"
          disabled={saving}
          className="w-full h-11 rounded-md bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition disabled:opacity-50"
        >
          {saving ? 'Creating…' : 'Create project'}
        </button>
      </form>
    </div>
  )
}
