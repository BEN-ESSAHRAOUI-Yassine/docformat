import { useParams } from 'react-router-dom'

export default function DocumentView() {
  const { id } = useParams()
  return (
    <div>
      <h1 className="text-2xl font-semibold text-slate-900">Document View</h1>
      <p className="mt-2 text-slate-600">Document ID: {id}</p>
    </div>
  )
}
