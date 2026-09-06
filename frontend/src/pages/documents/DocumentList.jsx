import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { FileText, Upload, FolderKanban } from 'lucide-react'
import { listProjects } from '../../api/projects'
import { listDocuments } from '../../api/documents'
import { Badge } from '../../components/ui/badge'
import { Skeleton } from '../../components/ui/skeleton'

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

export default function DocumentList() {
  const [projects, setProjects] = useState([])
  const [projectId, setProjectId] = useState('')
  const [documents, setDocuments] = useState([])
  const [loadingProjects, setLoadingProjects] = useState(true)
  const [loadingDocs, setLoadingDocs] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    listProjects()
      .then((data) => {
        const list = data.data || data || []
        setProjects(list)
        if (list[0]) setProjectId(String(list[0].id))
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoadingProjects(false))
  }, [])

  useEffect(() => {
    if (!projectId) return
    setLoadingDocs(true)
    listDocuments(projectId)
      .then((data) => {
        const list = data.data || data.documents || data || []
        setDocuments(list)
        setError(null)
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoadingDocs(false))
  }, [projectId])

  return (
    <div>
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">Documents</h1>
          <p className="mt-1 text-slate-500">Manage your documents.</p>
        </div>
        <Link
          to="/documents/upload"
          className="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700 transition"
        >
          <Upload size={16} />
          Upload
        </Link>
      </div>

      {loadingProjects ? (
        <Skeleton className="mt-8 h-40 w-full" />
      ) : projects.length === 0 ? (
        <div className="mt-8 rounded-xl border border-slate-200 bg-white p-10 text-center">
          <FolderKanban className="mx-auto h-10 w-10 text-slate-300" />
          <h2 className="mt-4 text-lg font-semibold text-slate-900">No projects yet</h2>
          <p className="mt-1 text-slate-500">Create a project to start organizing documents.</p>
        </div>
      ) : (
        <>
          <div className="mt-6 max-w-sm">
            <label className="text-sm font-medium text-slate-700">Project</label>
            <select
              value={projectId}
              onChange={(e) => setProjectId(e.target.value)}
              className="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {projects.map((p) => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </select>
          </div>

          {loadingDocs && <Skeleton className="mt-6 h-40 w-full" />}

          {!loadingDocs && documents.length === 0 && (
            <div className="mt-6 rounded-xl border border-slate-200 bg-white p-10 text-center">
              <FileText className="mx-auto h-10 w-10 text-slate-300" />
              <h2 className="mt-4 text-lg font-semibold text-slate-900">No documents in this project</h2>
              <p className="mt-1 text-slate-500">Upload a DOCX to begin analysis.</p>
              <Link to="/documents/upload" className="mt-4 inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                <Upload size={16} />
                Upload document
              </Link>
            </div>
          )}

          {!loadingDocs && documents.length > 0 && (
            <div className="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
              {documents.map((doc) => (
                <Link key={doc.id} to={`/documents/${doc.id}`} className="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition">
                  <div className="flex items-center gap-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                      <FileText size={18} />
                    </span>
                    <div>
                      <p className="font-medium text-slate-900">{doc.name}</p>
                      <p className="text-xs text-slate-500">{new Date(doc.created_at).toLocaleDateString()}</p>
                    </div>
                  </div>
                  <Badge variant={statusVariant[doc.status] || 'default'}>{doc.status?.replace(/_/g, ' ')}</Badge>
                </Link>
              ))}
            </div>
          )}
        </>
      )}

      {error && !loadingDocs && (
        <div className="mt-6 rounded-lg border border-red-200 bg-red-50 p-6 text-red-700">
          Error: {error}
        </div>
      )}
    </div>
  )
}
