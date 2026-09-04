import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { analyzeIntelligence, getSimilarity, toggleAi } from '../../api/intelligence'
import { Button } from '../../components/ui/button'
import { Card } from '../../components/ui/card'

export default function IntelligencePanel() {
  const { id } = useParams()
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState(null)
  const [similarity, setSimilarity] = useState(null)
  const [aiEnabled, setAiEnabled] = useState(false)

  const runAnalysis = async () => {
    setBusy(true)
    setMessage(null)
    try {
      await analyzeIntelligence(id)
      const sim = await getSimilarity(id)
      setSimilarity(sim)
      setMessage('Intelligence analysis completed.')
    } catch (err) {
      setMessage(`Error: ${err.message}`)
    } finally {
      setBusy(false)
    }
  }

  const handleToggle = async (enabled) => {
    try {
      const data = await toggleAi(id, enabled)
      setAiEnabled(data.ai_enabled)
    } catch (err) {
      setMessage(`Error: ${err.message}`)
    }
  }

  return (
    <div className="flex flex-col h-full">
      <div className="border-b border-slate-200 p-4">
        <h2 className="text-lg font-semibold text-slate-900">Intelligence</h2>
        <p className="text-sm text-slate-500">Similarity and AI-content analysis (estimates only).</p>
      </div>

      <div className="p-3 space-y-3">
        <Card className="p-3">
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-700">AI content analysis</span>
            <button
              onClick={() => handleToggle(!aiEnabled)}
              className={`relative h-6 w-11 rounded-full transition ${aiEnabled ? 'bg-blue-600' : 'bg-slate-300'}`}
            >
              <span className={`absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition ${aiEnabled ? 'translate-x-5' : ''}`} />
            </button>
          </div>
        </Card>

        <Card className="p-3">
          <h3 className="text-sm font-medium text-slate-700">Similarity score</h3>
          <p className="mt-1 text-2xl font-bold text-blue-600">{similarity ? `${similarity.overall}%` : '—'}</p>
          {similarity && similarity.matches.length > 0 && (
            <ul className="mt-2 space-y-1 text-xs text-slate-500">
              {similarity.matches.slice(0, 5).map((m, i) => (
                <li key={i}>{m.source} ({m.confidence} confidence)</li>
              ))}
            </ul>
          )}
        </Card>

        <Button size="sm" className="w-full" onClick={runAnalysis} disabled={busy}>
          {busy ? 'Running…' : 'Run intelligence analysis'}
        </Button>

        {message && <p className="text-sm text-slate-600">{message}</p>}

        <p className="text-xs text-slate-400">
          AI and similarity results are estimates, never definitive. Similarity is compared against your
          own documents; external processing (when enabled) is clearly indicated.
        </p>
      </div>
    </div>
  )
}
