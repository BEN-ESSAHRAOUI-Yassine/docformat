import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { getActions, undo, redo } from '../../api/actions'
import { Button } from '../../components/ui/button'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

export default function HistoryPanel() {
  const { id } = useParams()
  const [actions, setActions] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(false)

  const loadActions = async () => {
    setLoading(true)
    try {
      const data = await getActions(id, { limit: 20 })
      setActions(data.actions ?? [])
      setError(null)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadActions()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id])

  const perform = async (fn) => {
    setBusy(true)
    try {
      await fn(id)
      await loadActions()
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="flex flex-col h-full">
      <div className="border-b border-slate-200 p-4 flex items-center justify-between">
        <h2 className="text-lg font-semibold text-slate-900">History</h2>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" disabled={busy} onClick={() => perform(undo)}>Undo</Button>
          <Button size="sm" variant="outline" disabled={busy} onClick={() => perform(redo)}>Redo</Button>
        </div>
      </div>

      <div className="flex-1 overflow-y-auto p-3 space-y-2">
        {loading && <Skeleton className="h-16 w-full" />}
        {error && <p className="text-red-600 text-sm">Error: {error}</p>}

        {!loading && actions.length === 0 && (
          <p className="text-sm text-slate-500">No actions recorded.</p>
        )}

        {!loading && actions.map((action) => (
          <Card key={action.id} className="p-3">
            <div className="flex items-center justify-between">
              <span className="text-sm font-medium text-slate-800">{action.action_type.replace(/_/g, ' ')}</span>
              <span className="text-xs text-slate-400 capitalize">{action.origin}</span>
            </div>
            <p className="mt-1 text-xs text-slate-500">
              {new Date(action.created_at).toLocaleString()}
              {!action.is_reversible && <span className="ml-2 text-amber-600">not reversible</span>}
            </p>
          </Card>
        ))}
      </div>
    </div>
  )
}
