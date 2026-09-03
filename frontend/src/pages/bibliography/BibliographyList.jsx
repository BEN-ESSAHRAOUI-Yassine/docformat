import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import { getBibliography } from '../../api/bibliography'
import { Badge } from '../../components/ui/badge'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

export default function BibliographyList() {
  const { id } = useParams()
  const [entries, setEntries] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchBibliography = async () => {
      try {
        const data = await getBibliography(id)
        setEntries(data.bibliography || [])
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }
    fetchBibliography()
  }, [id])

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64 w-full" />
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

  const getTypeColor = (type) => {
    const colors = {
      article: 'bg-blue-50 text-blue-700',
      book: 'bg-purple-50 text-purple-700',
      chapter: 'bg-green-50 text-green-700',
      conference: 'bg-orange-50 text-orange-700',
      online: 'bg-cyan-50 text-cyan-700',
      thesis: 'bg-pink-50 text-pink-700',
    }
    return colors[type] || 'bg-slate-50 text-slate-700'
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold text-slate-900">Bibliography</h1>
      <p className="mt-2 text-slate-600">{entries.length} entries found</p>

      {entries.length === 0 ? (
        <Card className="mt-6 p-6">
          <p className="text-slate-500">No bibliography entries detected.</p>
        </Card>
      ) : (
        <div className="mt-6 space-y-4">
          {entries.map((entry) => (
            <Link
              key={entry.id}
              to={`/documents/${id}/bibliography/${entry.id}`}
              className="block"
            >
              <Card className="p-4 hover:shadow-md transition-shadow">
                <div className="flex items-start justify-between">
                  <div>
                    <div className="flex items-center gap-2">
                      <Badge className={getTypeColor(entry.entry_type)}>
                        {entry.entry_type}
                      </Badge>
                      {entry.is_duplicate && (
                        <Badge variant="destructive">Duplicate</Badge>
                      )}
                      {entry.citations?.length > 0 ? (
                        <Badge variant="outline" className="bg-green-50 text-green-700">
                          Cited ({entry.citations.length})
                        </Badge>
                      ) : (
                        <Badge variant="outline" className="bg-yellow-50 text-yellow-700">
                          Uncited
                        </Badge>
                      )}
                    </div>
                    <p className="mt-2 text-slate-900 font-medium">{entry.title}</p>
                    <div className="mt-1 text-sm text-slate-500">
                      {entry.authors?.join(', ')}
                      {entry.year && <span> ({entry.year})</span>}
                    </div>
                    {entry.journal && (
                      <p className="mt-1 text-sm text-slate-500 italic">{entry.journal}</p>
                    )}
                  </div>
                  <span className="text-xs text-slate-400">Index: {entry.element_index}</span>
                </div>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
