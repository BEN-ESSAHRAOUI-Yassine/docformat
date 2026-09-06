import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import { getAnalysis } from '../../api/documents'
import { Skeleton } from '../../components/ui/skeleton'

const headingStyles = {
  1: 'text-2xl font-bold text-slate-900 mt-6 mb-3',
  2: 'text-xl font-semibold text-slate-900 mt-5 mb-2',
  3: 'text-lg font-semibold text-slate-800 mt-4 mb-2',
  4: 'text-base font-semibold text-slate-800 mt-3 mb-1.5',
  5: 'text-base font-medium text-slate-700 mt-3 mb-1.5',
  6: 'text-sm font-medium text-slate-700 mt-2 mb-1',
}

function PageSheet({ children, orientation, pageNumber, showMarks }) {
  const isLandscape = orientation === 'landscape'
  return (
    <div className="mx-auto my-6 flex flex-col bg-white shadow-sm ring-1 ring-slate-200">
      <div
        className={`flex-1 bg-white p-10 ${
          isLandscape ? 'aspect-[1.414/1]' : 'aspect-[1/1.414]'
        }`}
        style={{ width: '100%', maxWidth: isLandscape ? 900 : 640 }}
      >
        <div className="h-full overflow-hidden">{children}</div>
      </div>
      <div className="flex items-center justify-between border-t border-slate-100 px-10 py-2 text-xs text-slate-400">
        <span>{isLandscape ? 'Landscape' : 'Portrait'}</span>
        <span>Page {pageNumber}</span>
      </div>
    </div>
  )
}

function RenderElement({ el, showMarks }) {
  if (el.type === 'heading') {
    const level = el.heading_level || 1
    return <h2 className={headingStyles[level] || headingStyles[1]}>{el.content}</h2>
  }

  if (el.type === 'paragraph') {
    return (
      <p className="text-[14px] leading-7 text-slate-800 text-justify mb-2">
        {el.content}
        {showMarks && <span className="text-slate-300"> ¶</span>}
      </p>
    )
  }

  if (el.type === 'table') {
    const rows = el.metadata?.content || []
    return (
      <div className="overflow-x-auto my-3">
        <table className="w-full border-collapse text-[13px]">
          <tbody>
            {rows.map((row, i) => (
              <tr key={i} className={i === 0 ? 'bg-slate-50 font-medium' : ''}>
                {(row || []).map((cell, j) => (
                  <td key={j} className="border border-slate-200 px-2 py-1.5 text-slate-700">{cell}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    )
  }

  if (el.type === 'caption') {
    return <p className="text-[13px] italic text-slate-500 text-center my-2">{el.content}</p>
  }

  if (el.type === 'source') {
    return <p className="text-xs text-slate-400 text-right my-1">{el.content}</p>
  }

  if (el.content) {
    return <p className="text-[14px] leading-7 text-slate-800 mb-2">{el.content}</p>
  }

  return null
}

export default function DocumentViewer({ showMarks }) {
  const { id } = useParams()
  const [elements, setElements] = useState([])
  const [sections, setSections] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    getAnalysis(id)
      .then((data) => {
        const analysis = data.data ?? data
        setElements(analysis?.elements ?? [])
        setSections(analysis?.metadata?.sections ?? [])
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [id])

  if (loading) {
    return (
      <div className="space-y-3">
        <Skeleton className="h-6 w-2/3" />
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-5/6" />
      </div>
    )
  }

  if (error) return <p className="text-red-600">Error: {error}</p>

  if (elements.length === 0) {
    return (
      <div className="py-16 text-center">
        <p className="text-slate-500">No document content yet. Run analysis to see it here.</p>
      </div>
    )
  }

  // Group elements into pages, splitting at page_break elements.
  const pages = []
  let current = []

  elements.forEach((el) => {
    if (el.type === 'page_break') {
      pages.push(current)
      current = []
    } else {
      current.push(el)
    }
  })
  pages.push(current)

  const orientationFor = (pageIndex) => {
    if (sections.length === 0) return 'portrait'
    const sectionIndex = Math.min(pageIndex, sections.length - 1)
    return sections[sectionIndex]?.orientation || 'portrait'
  }

  return (
    <div>
      {pages.map((page, i) => (
        <PageSheet
          key={i}
          orientation={orientationFor(i)}
          pageNumber={i + 1}
          showMarks={showMarks}
        >
          {page.length === 0 ? (
            <p className="text-xs text-slate-300">(page break)</p>
          ) : (
            <div className="space-y-0">
              {page.map((el) => <RenderElement key={el.id} el={el} showMarks={showMarks} />)}
            </div>
          )}
        </PageSheet>
      ))}
    </div>
  )
}
