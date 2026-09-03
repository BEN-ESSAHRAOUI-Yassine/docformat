import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { getIssues, acceptIssue, rejectIssue, editIssue, ignoreIssue, bulkDecideIssues } from '../../api/issues'
import { Badge } from '../../components/ui/badge'
import { Button } from '../../components/ui/button'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

const REVIEW_MODES = ['all', 'formatting', 'citations', 'bibliography', 'similarity', 'ai', 'grammar']

const severityVariant = {
  error: 'destructive',
  warning: 'warning',
  info: 'default',
}

export default function IssuePanel() {
  const { id } = useParams()
  const [issues, setIssues] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [mode, setMode] = useState('all')
  const [bulkBusy, setBulkBusy] = useState(false)
  const [ignoreFor, setIgnoreFor] = useState(null)

  const loadIssues = async () => {
    setLoading(true)
    try {
      const data = await getIssues(id, { review_mode: mode, per_page: 100 })
      setIssues(data.issues?.data ?? [])
      setError(null)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadIssues()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id, mode])

  const decide = async (issueId, action, payload) => {
    if (action === 'accept') await acceptIssue(id, issueId)
    if (action === 'reject') await rejectIssue(id, issueId)
    if (action === 'edit') await editIssue(id, issueId, payload)
    if (action === 'ignore') await ignoreIssue(id, issueId, payload)
    await loadIssues()
  }

  const openIgnore = (issueId) => setIgnoreFor(issueId)

  const submitIgnore = async (e) => {
    e.preventDefault()
    const reason = new FormData(e.target).get('reason') || ''
    await decide(ignoreFor, 'ignore', reason)
    setIgnoreFor(null)
  }

  const bulk = async (decision) => {
    setBulkBusy(true)
    try {
      await bulkDecideIssues(id, { decision, mode })
      await loadIssues()
    } finally {
      setBulkBusy(false)
    }
  }

  const pendingCount = issues.filter((i) => i.decision === 'pending').length

  return (
    <div className="flex flex-col h-full">
      <div className="border-b border-slate-200 p-4">
        <h2 className="text-lg font-semibold text-slate-900">Issues</h2>
        <p className="text-sm text-slate-500">{pendingCount} pending</p>
      </div>

      <div className="flex flex-wrap gap-1 p-3 border-b border-slate-200">
        {REVIEW_MODES.map((m) => (
          <button
            key={m}
            onClick={() => setMode(m)}
            className={`px-2.5 py-1 rounded-md text-xs font-medium capitalize ${
              mode === m ? 'bg-blue-100 text-blue-700' : 'text-slate-500 hover:bg-slate-100'
            }`}
          >
            {m}
          </button>
        ))}
      </div>

      <div className="flex gap-2 p-3 border-b border-slate-200">
        <Button size="sm" variant="outline" disabled={bulkBusy || pendingCount === 0} onClick={() => bulk('accept')}>
          Accept all
        </Button>
        <Button size="sm" variant="outline" disabled={bulkBusy || pendingCount === 0} onClick={() => bulk('reject')}>
          Reject all
        </Button>
      </div>

      <div className="flex-1 overflow-y-auto p-3 space-y-3">
        {loading && <Skeleton className="h-24 w-full" />}
        {error && <p className="text-red-600 text-sm">Error: {error}</p>}

        {!loading && !error && issues.length === 0 && (
          <p className="text-sm text-slate-500">No issues in this view.</p>
        )}

        {!loading && issues.map((issue) => (
          <Card key={issue.id} className="p-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Badge variant={severityVariant[issue.severity] || 'default'}>{issue.severity}</Badge>
                <span className="text-xs text-slate-400 capitalize">{issue.source}</span>
              </div>
              <Badge variant="outline">{issue.decision}</Badge>
            </div>
            <p className="mt-2 text-sm text-slate-800">{issue.description}</p>
            {issue.recommendation && (
              <p className="mt-1 text-xs text-slate-500">Suggested: {issue.recommendation}</p>
            )}
            {issue.ignored_reason && (
              <p className="mt-1 text-xs text-amber-600">Ignored: {issue.ignored_reason}</p>
            )}

            {issue.decision === 'pending' ? (
              <div className="mt-3 flex gap-2">
                <Button size="sm" onClick={() => decide(issue.id, 'accept')}>Accept</Button>
                <Button size="sm" variant="outline" onClick={() => decide(issue.id, 'reject')}>Reject</Button>
                <Button size="sm" variant="ghost" onClick={() => decide(issue.id, 'edit', issue.recommendation || '')}>Edit</Button>
                <Button size="sm" variant="ghost" onClick={() => openIgnore(issue.id)}>Ignore</Button>
              </div>
            ) : (
              <div className="mt-3 text-xs text-slate-400">Reviewed</div>
            )}
          </Card>
        ))}
      </div>

      {ignoreFor && (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4" onClick={() => setIgnoreFor(null)}>
          <Card className="w-full max-w-md p-6" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-semibold text-slate-900">Ignore issue</h3>
            <p className="text-sm text-slate-500">Provide a reason for ignoring this issue.</p>
            <form onSubmit={submitIgnore} className="mt-4">
              <textarea
                name="reason"
                className="w-full min-h-[80px] rounded-md border border-slate-300 p-2 text-sm"
                placeholder="Reason"
              />
              <div className="mt-3 flex justify-end gap-2">
                <Button type="button" variant="ghost" onClick={() => setIgnoreFor(null)}>Cancel</Button>
                <Button type="submit">Ignore</Button>
              </div>
            </form>
          </Card>
        </div>
      )}
    </div>
  )
}
