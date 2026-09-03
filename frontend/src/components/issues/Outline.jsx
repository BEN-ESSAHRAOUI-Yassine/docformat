import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { getAnalysis } from '../../api/documents'
import { Skeleton } from '../../components/ui/skeleton'

export default function Outline() {
  const { id } = useParams()
  const [elements, setElements] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const load = async () => {
      try {
        const data = await getAnalysis(id)
        const analysis = data.data ?? data
        setElements(analysis?.elements ?? [])
      } catch {
        // analysis may not exist yet
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [id])

  if (loading) return <Skeleton className="h-64 w-full" />

  const headings = elements.filter((el) => el.type === 'heading')

  return (
    <div className="p-4">
      <h2 className="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-3">Outline</h2>
      {headings.length === 0 ? (
        <p className="text-sm text-slate-500">No headings detected.</p>
      ) : (
        <ul className="space-y-1">
          {headings.map((el) => (
            <li key={el.id} style={{ paddingLeft: `${((el.heading_level || 1) - 1) * 12}px` }}>
              <span className="text-sm text-slate-700">{el.content}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
