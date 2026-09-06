import { useState, useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { UploadCloud, FileText, ArrowLeft, CheckCircle2 } from 'lucide-react'
import { listProjects } from '../../api/projects'
import { uploadDocument } from '../../api/documents'
import { Skeleton } from '../../components/ui/skeleton'

export default function DocumentUpload() {
  const navigate = useNavigate()
  const [projects, setProjects] = useState([])
  const [projectId, setProjectId] = useState('')
  const [file, setFile] = useState(null)
  const [loadingProjects, setLoadingProjects] = useState(true)
  const [uploading, setUploading] = useState(false)
  const [progress, setProgress] = useState(0)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)

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

  const onFileChange = (e) => {
    const f = e.target.files?.[0]
    if (f) setFile(f)
  }

  const handleUpload = async () => {
    if (!projectId || !file) {
      setError('Select a project and a DOCX file.')
      return
    }
    setUploading(true)
    setError(null)
    setProgress(0)
    try {
      const data = await uploadDocument(projectId, file, (p) => setProgress(p))
      const docId = data?.data?.id ?? data?.id
      setSuccess(true)
      setTimeout(() => navigate(`/documents/${docId}`), 800)
    } catch (err) {
      setError(err.response?.data?.message || err.message)
    } finally {
      setUploading(false)
    }
  }

  return (
    <div className="max-w-2xl">
      <Link to="/documents" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900">
        <ArrowLeft size={16} /> Back to documents
      </Link>
      <h1 className="mt-4 text-2xl font-semibold text-slate-900">Upload Document</h1>
      <p className="mt-1 text-slate-500">Upload a DOCX file for analysis. Your original is never modified.</p>

      {loadingProjects ? (
        <Skeleton className="mt-8 h-64 w-full" />
      ) : projects.length === 0 ? (
        <div className="mt-8 rounded-xl border border-slate-200 bg-white p-10 text-center">
          <p className="text-slate-500">You need a project before uploading documents.</p>
          <Link to="/projects/new" className="mt-4 inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
            Create a project
          </Link>
        </div>
      ) : (
        <div className="mt-8 rounded-xl border border-slate-200 bg-white p-6">
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

          <label
            className="mt-6 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center hover:border-blue-400 hover:bg-blue-50/50 transition"
          >
            <input type="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" onChange={onFileChange} className="hidden" />
            {file ? (
              <>
                <FileText className="h-10 w-10 text-blue-600" />
                <p className="mt-3 font-medium text-slate-900">{file.name}</p>
                <p className="text-xs text-slate-500">Click to choose a different file</p>
              </>
            ) : (
              <>
                <UploadCloud className="h-10 w-10 text-slate-400" />
                <p className="mt-3 font-medium text-slate-700">Drop a DOCX here or click to browse</p>
                <p className="text-xs text-slate-500">.docx files only</p>
              </>
            )}
          </label>

          {uploading && (
            <div className="mt-4">
              <div className="h-2 rounded-full bg-slate-100">
                <div className="h-2 rounded-full bg-blue-600 transition-all" style={{ width: `${progress}%` }} />
              </div>
              <p className="mt-1 text-xs text-slate-500">{progress}%</p>
            </div>
          )}

          {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
          {success && (
            <p className="mt-4 flex items-center gap-1 text-sm text-green-600">
              <CheckCircle2 size={16} /> Uploaded! Opening document…
            </p>
          )}

          <button
            onClick={handleUpload}
            disabled={uploading}
            className="mt-6 w-full h-11 rounded-md bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition disabled:opacity-50"
          >
            {uploading ? 'Uploading…' : 'Upload document'}
          </button>
        </div>
      )}
    </div>
  )
}
