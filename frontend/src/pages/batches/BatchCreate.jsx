import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { listProjects } from '../../api/projects'
import { listDocuments } from '../../api/documents'
import { createBatch } from '../../api/batches'
import { Button } from '../../components/ui/button'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

export default function BatchCreate() {
  const navigate = useNavigate()
  const [projects, setProjects] = useState([])
  const [projectId, setProjectId] = useState('')
  const [documents, setDocuments] = useState([])
  const [selected, setSelected] = useState([])
  const [name, setName] = useState('')
  const [loadingProjects, setLoadingProjects] = useState(true)
  const [loadingDocs, setLoadingDocs] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    listProjects()
      .then((data) => {
        setProjects(data.data || data || [])
        if (data.data?.[0]) setProjectId(data.data[0].id)
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoadingProjects(false))
  }, [])

  useEffect(() => {
    if (!projectId) return
    setLoadingDocs(true)
    listDocuments(projectId)
      .then((data) => {
        setDocuments(data.data || data.documents || [])
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoadingDocs(false))
  }, [projectId])

  const toggle = (id) => {
    setSelected((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
    )
  }

  const submit = async () => {
    if (!name || selected.length === 0) {
      setError('A name and at least one document are required.')
      return
    }
    setBusy(true)
    setError(null)
    try {
      const data = await createBatch({ name, project_id: Number(projectId), document_ids: selected })
      navigate(`/batches/${data.batch.id}`)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="max-w-3xl mx-auto">
      <h1 className="text-2xl font-semibold text-slate-900">New batch</h1>
      <p className="mt-2 text-slate-600">Select a project and documents to process together.</p>

      {loadingProjects && <Skeleton className="mt-6 h-20 w-full" />}
      {error && <p className="mt-4 text-red-600">Error: {error}</p>}

      <Card className="mt-6 p-6 space-y-4">
        <div>
          <label className="text-sm font-medium text-slate-700">Batch name</label>
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="mt-1 w-full h-10 rounded-md border border-slate-300 px-2 text-sm"
            placeholder="e.g. Portfolio review"
          />
        </div>

        <div>
          <label className="text-sm font-medium text-slate-700">Project</label>
          <select
            value={projectId}
            onChange={(e) => setProjectId(e.target.value)}
            className="mt-1 w-full h-10 rounded-md border border-slate-300 px-2 text-sm"
          >
            {projects.map((p) => (
              <option key={p.id} value={p.id}>{p.name}</option>
            ))}
          </select>
        </div>

        {loadingDocs && <Skeleton className="h-40 w-full" />}

        {!loadingDocs && documents.length > 0 && (
          <div>
            <label className="text-sm font-medium text-slate-700">
              Documents ({selected.length} selected)
            </label>
            <div className="mt-2 space-y-2 max-h-64 overflow-y-auto">
              {documents.map((doc) => (
                <label key={doc.id} className="flex items-center gap-3 rounded-md border border-slate-200 p-2">
                  <input
                    type="checkbox"
                    checked={selected.includes(doc.id)}
                    onChange={() => toggle(doc.id)}
                  />
                  <span className="text-sm text-slate-800">{doc.name}</span>
                </label>
              ))}
            </div>
          </div>
        )}

        {!loadingDocs && documents.length === 0 && (
          <p className="text-sm text-slate-500">No documents in this project.</p>
        )}

        <div className="flex justify-end">
          <Button onClick={submit} disabled={busy}>
            {busy ? 'Creating…' : 'Create batch'}
          </Button>
        </div>
      </Card>
    </div>
  )
}
