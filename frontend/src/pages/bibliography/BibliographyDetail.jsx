import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import { getBibliographyCitations } from '../../api/bibliography'
import { Badge } from '../../components/ui/badge'
import { Card } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Skeleton } from '../../components/ui/skeleton'

export default function BibliographyDetail() {
  const { id, entryId } = useParams()
  const [entry, setEntry] = useState(null)
  const [citations, setCitations] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchData = async () => {
      try {
        const data = await getBibliographyCitations(id, entryId)
        setEntry(data.bibliography_entry)
        setCitations(data.citations || [])
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }
    fetchData()
  }, [id, entryId])

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-32 w-full" />
        <Skeleton className="h-48 w-full" />
      </div>
    )
  }

  if (error) {
    return (
      <Card className="p-6">
        <p className="text-red-600">Error: {error}</p>
      </Card>
    )
  }

  if (!entry) {
    return (
      <Card className="p-6">
        <p className="text-slate-500">Entry not found.</p>
      </Card>
    )
  }

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-slate-900">Bibliography Entry</h1>
        <Link to={`/documents/${id}/bibliography`}>
          <Button variant="outline">Back to List</Button>
        </Link>
      </div>

      <Card className="mt-6 p-6">
        <div className="flex items-center gap-2 mb-4">
          <Badge>{entry.entry_type}</Badge>
          {entry.is_duplicate && <Badge variant="destructive">Duplicate</Badge>}
        </div>

        <h2 className="text-xl font-medium text-slate-900">{entry.title}</h2>

        <div className="mt-4 space-y-2 text-sm">
          <p><span className="font-medium text-slate-700">Authors:</span> {entry.authors?.join(', ')}</p>
          {entry.year && <p><span className="font-medium text-slate-700">Year:</span> {entry.year}</p>}
          {entry.journal && <p><span className="font-medium text-slate-700">Journal:</span> {entry.journal}</p>}
          {entry.volume && <p><span className="font-medium text-slate-700">Volume:</span> {entry.volume}</p>}
          {entry.issue && <p><span className="font-medium text-slate-700">Issue:</span> {entry.issue}</p>}
          {entry.pages && <p><span className="font-medium text-slate-700">Pages:</span> {entry.pages}</p>}
          {entry.doi && <p><span className="font-medium text-slate-700">DOI:</span> {entry.doi}</p>}
          {entry.url && <p><span className="font-medium text-slate-700">URL:</span> {entry.url}</p>}
        </div>

        <div className="mt-6">
          <h3 className="text-lg font-medium text-slate-900">Raw Text</h3>
          <p className="mt-2 text-slate-600 text-sm bg-slate-50 p-3 rounded">{entry.raw_text}</p>
        </div>
      </Card>

      <div className="mt-6">
        <h3 className="text-lg font-medium text-slate-900">Citing Citations ({citations.length})</h3>
        {citations.length === 0 ? (
          <Card className="mt-4 p-4">
            <p className="text-slate-500">No citations link to this entry.</p>
          </Card>
        ) : (
          <div className="mt-4 space-y-3">
            {citations.map((citation) => (
              <Card key={citation.id} className="p-4">
                <div className="flex items-center gap-2">
                  <Badge variant="secondary">{citation.type}</Badge>
                  <span className="text-slate-900">{citation.raw_text}</span>
                </div>
              </Card>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
