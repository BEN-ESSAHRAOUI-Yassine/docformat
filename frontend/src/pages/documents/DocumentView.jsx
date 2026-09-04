import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { Button } from '../../components/ui/button'
import { createPageBreak } from '../../api/pageBreaks'
import IssuePanel from '../../components/issues/IssuePanel'
import HistoryPanel from '../../components/issues/HistoryPanel'
import IntelligencePanel from '../../components/issues/IntelligencePanel'
import Outline from '../../components/issues/Outline'

export default function DocumentView() {
  const { id } = useParams()
  const [rightTab, setRightTab] = useState('issues')
  const [showMarks, setShowMarks] = useState(false)
  const [elementIndex, setElementIndex] = useState('')
  const [context, setContext] = useState('chapter')
  const [message, setMessage] = useState(null)

  const addPageBreak = async () => {
    try {
      await createPageBreak(id, { context, element_index: elementIndex })
      setMessage('Page break inserted.')
      setElementIndex('')
    } catch (err) {
      setMessage(`Error: ${err.message}`)
    }
  }

  return (
    <div className="flex h-[calc(100vh-4rem)] gap-0">
      <aside className="w-56 border-r border-slate-200 bg-white overflow-y-auto">
        <Outline />
      </aside>

      <main className="flex-1 overflow-y-auto p-6">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold text-slate-900">Document</h1>
          <div className="flex items-center gap-2">
            <Button size="sm" variant="outline" onClick={() => setShowMarks((v) => !v)}>
              {showMarks ? 'Hide' : 'Show'} paragraph marks
            </Button>
          </div>
        </div>

        <div className="mt-6 border border-slate-200 rounded-lg bg-white p-8 min-h-[400px]">
          <div className="space-y-4">
            <p className="text-slate-600 text-justify">
              Document preview would render here. The structural viewer and navigation highlight
              in-text citations and paragraphs once analysis is complete.
            </p>
            {showMarks && (
              <p className="text-slate-400 text-sm">
                _Paragraph mark (interface only)__
              </p>
            )}
          </div>
        </div>

        <div className="mt-6 border-t border-slate-200 pt-4">
          <h2 className="text-sm font-semibold text-slate-700 mb-2">Insert page break</h2>
          <div className="flex gap-2 flex-wrap items-end">
            <select
              value={context}
              onChange={(e) => setContext(e.target.value)}
              className="h-10 rounded-md border border-slate-300 px-2 text-sm"
            >
              <option value="chapter">Chapter</option>
              <option value="section">Section</option>
              <option value="figure">Figure</option>
              <option value="table">Table</option>
              <option value="appendix">Appendix</option>
            </select>
            <input
              value={elementIndex}
              onChange={(e) => setElementIndex(e.target.value)}
              placeholder="Element index (optional)"
              className="h-10 w-48 rounded-md border border-slate-300 px-2 text-sm"
            />
            <Button size="sm" onClick={addPageBreak}>Insert</Button>
          </div>
          {message && <p className="mt-2 text-sm text-slate-500">{message}</p>}
        </div>
      </main>

      <aside className="w-80 border-l border-slate-200 bg-white flex flex-col">
        <div className="flex border-b border-slate-200">
          <button
            onClick={() => setRightTab('issues')}
            className={`flex-1 py-2 text-sm font-medium ${
              rightTab === 'issues' ? 'border-b-2 border-blue-600 text-blue-700' : 'text-slate-500'
            }`}
          >
            Issues
          </button>
          <button
            onClick={() => setRightTab('history')}
            className={`flex-1 py-2 text-sm font-medium ${
              rightTab === 'history' ? 'border-b-2 border-blue-600 text-blue-700' : 'text-slate-500'
            }`}
          >
            History
          </button>
          <button
            onClick={() => setRightTab('intelligence')}
            className={`flex-1 py-2 text-sm font-medium ${
              rightTab === 'intelligence' ? 'border-b-2 border-blue-600 text-blue-700' : 'text-slate-500'
            }`}
          >
            AI
          </button>
        </div>
        {rightTab === 'issues' ? <IssuePanel /> : rightTab === 'history' ? <HistoryPanel /> : <IntelligencePanel />}
      </aside>
    </div>
  )
}
