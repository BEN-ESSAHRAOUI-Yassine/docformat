import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { listBatches } from '../../api/batches'
import { Card } from '../../components/ui/card'
import { Badge } from '../../components/ui/badge'
import { Skeleton } from '../../components/ui/skeleton'

const statusVariant = {
  queued: 'default',
  processing: 'warning',
  completed: 'success',
  partial: 'warning',
  failed: 'destructive',
}

export default function BatchList() {
  const [batches, setBatches] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const load = async () => {
      try {
        const data = await listBatches()
        setBatches(data.batches || [])
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [])

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-slate-900">Batches</h1>
        <Link
          to="/batches/create"
          className="inline-flex items-center justify-center rounded-md bg-blue-600 h-10 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          New batch
        </Link>
      </div>

      {loading && <Skeleton className="mt-6 h-64 w-full" />}
      {error && <p className="mt-6 text-red-600">Error: {error}</p>}

      {!loading && !error && batches.length === 0 && (
        <Card className="mt-6 p-6">
          <p className="text-slate-500">No batches yet.</p>
        </Card>
      )}

      {!loading && batches.length > 0 && (
        <div className="mt-6 space-y-4">
          {batches.map((batch) => (
            <Link key={batch.id} to={`/batches/${batch.id}`}>
              <Card className="p-4 hover:shadow-md transition">
                <div className="flex items-center justify-between">
                  <div>
                    <h2 className="font-semibold text-slate-900">{batch.name}</h2>
                    <p className="text-sm text-slate-500">
                      {batch.summary?.total ?? 0} documents · avg score {batch.summary?.average_score ?? '—'}
                    </p>
                  </div>
                  <Badge variant={statusVariant[batch.status] || 'default'}>{batch.status}</Badge>
                </div>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
