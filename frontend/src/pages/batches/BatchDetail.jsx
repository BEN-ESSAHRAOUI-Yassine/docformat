import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { getBatch, getBatchItems, exportBatch, downloadBatchExport } from '../../api/batches'
import { Badge } from '../../components/ui/badge'
import { Button } from '../../components/ui/button'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

const statusVariant = {
  queued: 'default',
  processing: 'warning',
  completed: 'success',
  partial: 'warning',
  failed: 'destructive',
}

export default function BatchDetail() {
  const { batchId } = useParams()
  const [batch, setBatch] = useState(null)
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [exporting, setExporting] = useState(false)
  const [message, setMessage] = useState(null)

  const load = async () => {
    setLoading(true)
    try {
      const [b, i] = await Promise.all([getBatch(batchId), getBatchItems(batchId)])
      setBatch(b.batch)
      setItems(i.items || [])
      setError(null)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
    const timer = setInterval(() => {
      if (batch?.status === 'queued' || batch?.status === 'processing') load()
    }, 4000)
    return () => clearInterval(timer)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [batchId, batch?.status])

  const doExport = async () => {
    setExporting(true)
    setMessage(null)
    try {
      await exportBatch(batchId)
      setMessage('Export started.')
    } catch (err) {
      setMessage(`Export error: ${err.message}`)
    } finally {
      setExporting(false)
    }
  }

  const doDownload = async () => {
    try {
      const response = await downloadBatchExport(batchId)
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.download = `batch-${batchId}.zip`
      link.click()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      setMessage(`Download error: ${err.message}`)
    }
  }

  if (loading) return <div className="space-y-4"><Skeleton className="h-32 w-full" /><Skeleton className="h-64 w-full" /></div>

  if (error) return <p className="text-red-600">Error: {error}</p>

  const summary = batch?.summary || {}

  return (
    <div className="max-w-4xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">{batch.name}</h1>
          <p className="mt-1 text-slate-500">
            {summary.total ?? 0} documents · avg score {summary.average_score ?? '—'}
          </p>
        </div>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={doExport} disabled={exporting}>
            {exporting ? 'Exporting…' : 'Export all'}
          </Button>
          <Button size="sm" onClick={doDownload}>Download ZIP</Button>
        </div>
      </div>

      {message && <p className="mt-3 text-sm text-slate-600">{message}</p>}

      <Card className="mt-6 p-6">
        <h2 className="text-lg font-semibold text-slate-900">Summary</h2>
        <div className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div><p className="text-xs text-slate-400">Total</p><p className="text-xl font-bold text-slate-900">{summary.total ?? 0}</p></div>
          <div><p className="text-xs text-slate-400">Completed</p><p className="text-xl font-bold text-green-600">{summary.completed ?? 0}</p></div>
          <div><p className="text-xs text-slate-400">Failed</p><p className="text-xl font-bold text-red-600">{summary.failed ?? 0}</p></div>
          <div><p className="text-xs text-slate-400">Avg score</p><p className="text-xl font-bold text-blue-600">{summary.average_score ?? '—'}</p></div>
        </div>
      </Card>

      <Card className="mt-6 p-6">
        <h2 className="text-lg font-semibold text-slate-900">Documents</h2>
        <div className="mt-4 space-y-3">
          {items.map((item) => (
            <div key={item.id} className="flex items-center justify-between rounded-md border border-slate-200 p-3">
              <span className="text-sm text-slate-800">{item.document?.name}</span>
              <div className="flex items-center gap-3">
                <Badge variant={statusVariant[item.status] || 'default'}>{item.status}</Badge>
                <span className="text-sm font-medium text-slate-700">{item.quality_score ?? '—'}</span>
              </div>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}
