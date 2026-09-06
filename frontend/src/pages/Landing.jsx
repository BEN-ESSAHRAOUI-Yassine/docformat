import { Link } from 'react-router-dom'
import { ArrowRight, FileText, CheckCircle2, Sparkles, BookOpen, Layers } from 'lucide-react'

const features = [
  {
    icon: FileText,
    title: 'Automatic structure analysis',
    description: 'Detect headings, figures, tables, and captions the moment you upload a DOCX.',
  },
  {
    icon: BookOpen,
    title: 'Citation & bibliography engine',
    description: 'Two-way validation, duplicate detection, and multi-style formatting built in.',
  },
  {
    icon: Sparkles,
    title: 'AI-assisted review',
    description: 'Similarity, correction, and paraphrasing suggestions presented as estimates, never facts.',
  },
  {
    icon: Layers,
    title: 'Batch processing',
    description: 'Run the same quality pipeline over many documents and export results together.',
  },
]

const workflow = [
  'Upload your DOCX',
  'Select a style profile',
  'Review detected issues',
  'Export a clean document',
]

export default function Landing() {
  return (
    <div className="min-h-[100dvh] bg-white text-slate-900">
      <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur">
        <nav className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
          <div className="flex items-center gap-2">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white">
              <FileText size={18} />
            </span>
            <span className="text-lg font-semibold tracking-tight">DocFormat</span>
          </div>
          <div className="flex items-center gap-3">
            <Link
              to="/login"
              className="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition"
            >
              Sign in
            </Link>
            <Link
              to="/register"
              className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition"
            >
              Get started
            </Link>
          </div>
        </nav>
      </header>

      <section className="mx-auto max-w-7xl px-6 pt-20 pb-16">
        <div className="grid items-center gap-12 lg:grid-cols-2">
          <div>
            <p className="mb-4 text-sm font-medium uppercase tracking-wide text-blue-600">
              Document intelligence platform
            </p>
            <h1 className="text-4xl font-bold tracking-tight md:text-5xl lg:text-6xl leading-[1.05]">
              Quality documents, reviewed by machine.
            </h1>
            <p className="mt-6 max-w-[52ch] text-lg text-slate-600 leading-relaxed">
              DocFormat analyzes structure, validates citations, enforces formatting rules, and
              surfaces issues — so every academic and professional document is clean before it ships.
            </p>
            <div className="mt-8 flex flex-wrap items-center gap-3">
              <Link
                to="/register"
                className="inline-flex h-11 items-center gap-2 rounded-md bg-blue-600 px-6 text-sm font-medium text-white hover:bg-blue-700 transition"
              >
                Start free
                <ArrowRight size={16} />
              </Link>
              <Link
                to="/login"
                className="inline-flex h-11 items-center rounded-md border border-slate-300 px-6 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
              >
                Sign in
              </Link>
            </div>
          </div>
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
            <div className="rounded-xl border border-slate-200 bg-white p-6">
              <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                <span className="text-sm font-semibold text-slate-900">Quality Report</span>
                <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                  94%
                </span>
              </div>
              <ul className="mt-4 space-y-3">
                {[
                  ['Structure', '95%'],
                  ['Citations', '91%'],
                  ['Bibliography', '94%'],
                  ['Style', '98%'],
                ].map(([label, value]) => (
                  <li key={label}>
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-slate-600">{label}</span>
                      <span className="font-medium text-slate-900">{value}</span>
                    </div>
                    <div className="mt-1 h-1.5 rounded-full bg-slate-100">
                      <div className="h-1.5 rounded-full bg-blue-600" style={{ width: value }} />
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section className="border-t border-slate-200 bg-slate-50 py-20">
        <div className="mx-auto max-w-7xl px-6">
          <h2 className="text-center text-3xl font-bold tracking-tight md:text-4xl">
            Everything a document needs
          </h2>
          <p className="mx-auto mt-4 max-w-[58ch] text-center text-slate-600">
            From upload to export, DocFormat handles the repetitive work so you can focus on content.
          </p>
          <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {features.map(({ icon: Icon, title, description }) => (
              <div key={title} className="rounded-xl border border-slate-200 bg-white p-6">
                <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                  <Icon size={20} />
                </span>
                <h3 className="mt-4 text-lg font-semibold text-slate-900">{title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">{description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-6 py-20">
        <h2 className="text-center text-3xl font-bold tracking-tight md:text-4xl">How it works</h2>
        <div className="mt-12 grid gap-6 md:grid-cols-4">
          {workflow.map((step, i) => (
            <div key={step} className="relative rounded-xl border border-slate-200 p-6">
              <span className="text-sm font-semibold text-blue-600">0{i + 1}</span>
              <p className="mt-3 font-medium text-slate-900">{step}</p>
              {i < workflow.length - 1 && (
                <ArrowRight size={16} className="absolute -right-3 top-1/2 hidden -translate-y-1/2 text-slate-300 md:block" />
              )}
            </div>
          ))}
        </div>
      </section>

      <section className="border-t border-slate-200 bg-slate-900 py-16 text-white">
        <div className="mx-auto max-w-7xl px-6 text-center">
          <h2 className="text-3xl font-bold tracking-tight">Ready to clean up your documents?</h2>
          <p className="mx-auto mt-4 max-w-[52ch] text-slate-300">
            Your original file is never modified. Every change is reviewable and reversible.
          </p>
          <Link
            to="/register"
            className="mt-8 inline-flex h-11 items-center gap-2 rounded-md bg-white px-6 text-sm font-medium text-slate-900 hover:bg-slate-100 transition"
          >
            Get started free
            <ArrowRight size={16} />
          </Link>
        </div>
      </section>

      <footer className="border-t border-slate-200 py-8">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 text-sm text-slate-500 sm:flex-row">
          <span className="flex items-center gap-2">
            <CheckCircle2 size={16} />
            DocFormat
          </span>
          <span>© {new Date().getFullYear()} DocFormat. All rights reserved.</span>
        </div>
      </footer>
    </div>
  )
}
