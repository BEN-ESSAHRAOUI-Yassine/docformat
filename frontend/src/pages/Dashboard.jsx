import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { FolderKanban, FileText, Layers, Gauge, ArrowRight, Plus, Upload } from 'lucide-react'
import { getDashboard } from '../api/dashboard'
import { Badge } from '../components/ui/badge'
import { Skeleton } from '../components/ui/skeleton'

const statusVariant = {
  uploaded: 'default',
  queued: 'default',
  analyzing: 'warning',
  analysis_completed: 'success',
  processing: 'warning',
  review_required: 'warning',
  ready_for_export: 'success',
  exporting: 'warning',
  completed: 'success',
  failed: 'destructive',
  archived: 'outline',
}

export default function Dashboard() {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    getDashboard()
      .then((d) => setData(d))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [])

  const counts = data?.counts || {}
  const stats = [
    { label: 'Projects', value: counts.projects ?? 0, icon: FolderKanban, to: null },
    { label: 'Documents', value: counts.documents ?? 0, icon: FileText, to: '/documents' },
    { label: 'Batches', value: counts.batches ?? 0, icon: Layers, to: '/batches' },
    { label: 'Avg quality', value: counts.average_quality ? `${counts.average_quality}%` : '—', icon: Gauge, to: null },
  ]

  return (
    <div>
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">Dashboard</h1>
          <p className="mt-1 text-slate-500">Overview of your documents and quality.</p>
        </div>
        <div className="flex gap-2">
          <Link
            to="/documents/upload"
            className="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700 transition"
          >
            <Upload size={16} />
            Upload
          </Link>
          <Link
            to="/batches/create"
            className="inline-flex h-10 items-center gap-2 rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
          >
            <Plus size={16} />
            New batch
          </Link>
        </div>
      </div>

      {loading && (
        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-24 w-full" />)}
        </div>
      )}

      {error && (
        <div className="mt-8 rounded-lg border border-red-200 bg-red-50 p-6 text-red-700">
          Error: {error}
        </div>
      )}

      {!loading && !error && (
        <>
          <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {stats.map(({ label, value, icon: Icon, to }) => {
              const inner = (
                <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-5">
                  <div>
                    <p className="text-sm text-slate-500">{label}</p>
                    <p className="mt-1 text-2xl font-semibold text-slate-900">{value}</p>
                  </div>
                  <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <Icon size={20} />
                  </span>
                </div>
              )
              return to ? <Link key={label} to={to} className="hover:shadow-md transition">{inner}</Link> : <div key={label}>{inner}</div>
            })}
          </div>

          <div className="mt-10 grid gap-6 lg:grid-cols-3">
            <div className="lg:col-span-2">
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold text-slate-900">Recent documents</h2>
                <Link to="/documents" className="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
                  View all <ArrowRight size={14} />
                </Link>
              </div>
              {data.recent_documents?.length ? (
                <div className="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                  {data.recent_documents.map((doc) => (
                    <Link key={doc.id} to={`/documents/${doc.id}`} className="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition">
                      <div className="flex items-center gap-3">
                        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                          <FileText size={18} />
                        </span>
                        <div>
                          <p className="font-medium text-slate-900">{doc.name}</p>
                          <p className="text-xs text-slate-500">{doc.project?.name}</p>
                        </div>
                      </div>
                      <Badge variant={statusVariant[doc.status] || 'default'}>{doc.status?.replace(/_/g, ' ')}</Badge>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="mt-4 rounded-xl border border-slate-200 bg-white p-8 text-center">
                  <p className="text-slate-500">No documents yet.</p>
                  <Link to="/documents/upload" className="mt-3 inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
                    Upload your first document <ArrowRight size={14} />
                  </Link>
                </div>
              )}
            </div>

            <div>
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold text-slate-900">Recent batches</h2>
                <Link to="/batches" className="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
                  View all <ArrowRight size={14} />
                </Link>
              </div>
              {data.recent_batches?.length ? (
                <div className="mt-4 space-y-3">
                  {data.recent_batches.map((batch) => (
                    <Link key={batch.id} to={`/batches/${batch.id}`} className="block rounded-xl border border-slate-200 bg-white p-4 hover:shadow-md transition">
                      <p className="font-medium text-slate-900">{batch.name}</p>
                      <p className="mt-1 text-xs text-slate-500">
                        {batch.summary?.total ?? 0} documents · avg {batch.summary?.average_score ?? '—'}
                      </p>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="mt-4 rounded-xl border border-slate-200 bg-white p-8 text-center">
                  <p className="text-slate-500">No batches yet.</p>
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
