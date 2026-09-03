import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { getAbbreviations } from '../../api/abbreviations'
import { Badge } from '../../components/ui/badge'
import { Card } from '../../components/ui/card'
import { Skeleton } from '../../components/ui/skeleton'

export default function AbbreviationList() {
  const { id } = useParams()
  const [abbreviations, setAbbreviations] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchAbbreviations = async () => {
      try {
        const data = await getAbbreviations(id)
        setAbbreviations(data.abbreviations || [])
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }
    fetchAbbreviations()
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
      <h1 className="text-2xl font-semibold text-slate-900">Abbreviations</h1>
      <p className="mt-2 text-slate-600">{abbreviations.length} abbreviations found</p>

      {abbreviations.length === 0 ? (
        <Card className="mt-6 p-6">
          <p className="text-slate-500">No abbreviations detected in this document.</p>
        </Card>
      ) : (
        <div className="mt-6">
          <div className="overflow-hidden rounded-lg border border-slate-200">
            <table className="min-w-full divide-y divide-slate-200">
              <thead className="bg-slate-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Abbreviation</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Full Form</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Usage Count</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Defined At</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 bg-white">
                {abbreviations.map((abbr) => (
                  <tr key={abbr.id}>
                    <td className="px-4 py-3 text-sm font-medium text-slate-900">{abbr.abbreviation}</td>
                    <td className="px-4 py-3 text-sm text-slate-600">{abbr.full_form}</td>
                    <td className="px-4 py-3">
                      {abbr.is_consistent ? (
                        <Badge variant="outline" className="bg-green-50 text-green-700">Consistent</Badge>
                      ) : (
                        <Badge variant="destructive">Inconsistent</Badge>
                      )}
                    </td>
                    <td className="px-4 py-3 text-sm text-slate-600">{abbr.usage_count}</td>
                    <td className="px-4 py-3 text-sm text-slate-500">Index {abbr.definition_element_index}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}
