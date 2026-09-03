import IssuePanel from '../../components/issues/IssuePanel'

export default function DocumentIssues() {
  return (
    <div className="flex h-[calc(100vh-4rem)] gap-0">
      <main className="flex-1 p-6">
        <h1 className="text-2xl font-semibold text-slate-900">Quality Issues</h1>
        <p className="mt-2 text-slate-600">
          Review detected quality issues and accept, reject, edit, or ignore them.
        </p>
      </main>
      <aside className="w-[32rem] border-l border-slate-200 bg-white flex flex-col">
        <IssuePanel />
      </aside>
    </div>
  )
}
