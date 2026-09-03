import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { getCitations } from '../../api/citations'
import { Badge } from '../../components/ui/badge'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

export default function CitationList() {
  const { id } = useParams()
  const [citations, setCitations] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchCitations = async () => {
      try {
        const data = await getCitations(id)
        setCitations(data.citations || [])
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }
    fetchCitations()
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

  return (
    <div>
      <h1 className="text-2xl font-semibold text-slate-900">Citations</h1>
      <p className="mt-2 text-slate-600">{citations.length} citations found</p>

      {citations.length === 0 ? (
        <Card className="mt-6 p-6">
          <p className="text-slate-500">No citations detected in this document.</p>
        </Card>
      ) : (
        <div className="mt-6 space-y-4">
          {citations.map((citation) => (
            <Card key={citation.id} className="p-4">
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <Badge variant={citation.type === 'author_year' ? 'default' : 'secondary'}>
                      {citation.type}
                    </Badge>
                    {citation.bibliography_entry_id ? (
                      <Badge variant="outline" className="bg-green-50 text-green-700">Linked</Badge>
                    ) : (
                      <Badge variant="outline" className="bg-yellow-50 text-yellow-700">Orphan</Badge>
                    )}
                  </div>
                  <p className="mt-2 text-slate-900">{citation.raw_text}</p>
                  <div className="mt-2 text-sm text-slate-500">
                    {citation.author && <span>Author: {citation.author}</span>}
                    {citation.author && citation.year && <span> · </span>}
                    {citation.year && <span>Year: {citation.year}</span>}
                  </div>
                </div>
                <span className="text-xs text-slate-400">Index: {citation.element_index}</span>
              </div>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
