import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import { getQuality, getReport, generateReport } from '../../api/reports'
import { exportDocument, downloadExport } from '../../api/export'
import { Badge } from '../../components/ui/badge'
import { Button } from '../../components/ui/button'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

export default function QualityReport() {
  const { id } = useParams()
  const [quality, setQuality] = useState(null)
  const [report, setReport] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [exporting, setExporting] = useState(false)
  const [message, setMessage] = useState(null)

  const loadAll = async () => {
    setLoading(true)
    try {
      const [q, r] = await Promise.all([getQuality(id), getReport(id)])
      setQuality(q)
      setReport(r)
      setError(null)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadAll()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id])

  const handleExport = async () => {
    setExporting(true)
    setMessage(null)
    try {
      await exportDocument(id)
      setMessage('Export started. You can download once complete.')
    } catch (err) {
      setMessage(`Export error: ${err.message}`)
    } finally {
      setExporting(false)
    }
  }

  const handleDownload = async () => {
    try {
      const response = await downloadExport(id)
      const url = window.URL.createObjectURL(new Blob([response.data], { type: response.headers['content-type'] }))
      const link = document.createElement('a')
      link.href = url
      link.download = 'document.docx'
      link.click()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      setMessage(`Download error: ${err.message}`)
    }
  }

  const score = quality?.overall_score ?? report?.quality_score?.overall_score
  const categories = quality?.category_scores ?? report?.quality_score?.category_scores ?? {}
  const counts = quality?.counts ?? report?.quality_score?.counts

  return (
    <div className="max-w-4xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">Quality Report</h1>
          <p className="mt-1 text-slate-500">Document {id}</p>
        </div>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={loadAll}>Refresh</Button>
          <Button size="sm" variant="outline" onClick={handleExport} disabled={exporting}>
            {exporting ? 'Exporting…' : 'Export DOCX'}
          </Button>
          <Button size="sm" onClick={handleDownload}>Download</Button>
        </div>
      </div>

      {message && <p className="mt-3 text-sm text-slate-600">{message}</p>}

      {loading && (
        <div className="mt-6 space-y-4">
          <Skeleton className="h-40 w-full" />
          <Skeleton className="h-32 w-full" />
        </div>
      )}

      {error && !loading && (
        <Card className="mt-6 p-6">
          <p className="text-red-600">Error: {error}</p>
        </Card>
      )}

      {!loading && !error && (
        <>
          <Card className="mt-6 p-6">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-lg font-semibold text-slate-900">Overall Score</h2>
                <p className="text-sm text-slate-500">Deterministic weighted score</p>
              </div>
              <div className="text-5xl font-bold text-blue-600">{score ?? '—'}</div>
            </div>
            {counts && (
              <div className="mt-4 flex gap-3">
                <Badge variant="destructive">{counts.errors ?? 0} errors</Badge>
                <Badge variant="warning">{counts.warnings ?? 0} warnings</Badge>
                <Badge variant="default">{counts.info ?? 0} info</Badge>
              </div>
            )}
          </Card>

          <Card className="mt-6 p-6">
            <h2 className="text-lg font-semibold text-slate-900">Category Scores</h2>
            <div className="mt-4 space-y-3">
              {Object.entries(categories).map(([key, cat]) => (
                <div key={key}>
                  <div className="flex items-center justify-between">
                    <span className="text-sm capitalize text-slate-700">{key.replace(/_/g, ' ')}</span>
                    <span className="text-sm font-medium text-slate-900">{cat.score}</span>
                  </div>
                  <div className="mt-1 h-2 rounded-full bg-slate-100">
                    <div
                      className="h-2 rounded-full bg-blue-500"
                      style={{ width: `${cat.score}%` }}
                    />
                  </div>
                </div>
              ))}
            </div>
          </Card>

          {report?.sections && (
            <Card className="mt-6 p-6">
              <h2 className="text-lg font-semibold text-slate-900">Detection Summary</h2>
              <dl className="mt-4 grid grid-cols-2 gap-4">
                {Object.entries(report.sections).map(([key, section]) => (
                  <div key={key} className="rounded-md border border-slate-200 p-3">
                    <dt className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{key}</dt>
                    <dd className="mt-1 text-sm text-slate-800">
                      {Object.entries(section).map(([k, v]) => (
                        <div key={k}>{k.replace(/_/g, ' ')}: {v}</div>
                      ))}
                    </dd>
                  </div>
                ))}
              </dl>
            </Card>
          )}

          <p className="mt-6 text-xs text-slate-400">
            Export and PDF are DOCX now; PDF export is planned for a later sprint.
          </p>
        </>
      )}
    </div>
  )
}
