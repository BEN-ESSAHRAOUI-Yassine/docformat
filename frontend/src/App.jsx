import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './stores/authStore'

import Login from './pages/auth/Login'
import Register from './pages/auth/Register'
import ForgotPassword from './pages/auth/ForgotPassword'
import Dashboard from './pages/Dashboard'
import DocumentList from './pages/documents/DocumentList'
import DocumentUpload from './pages/documents/DocumentUpload'
import DocumentView from './pages/documents/DocumentView'
import DocumentIssues from './pages/documents/DocumentIssues'
import StyleProfileList from './pages/style-profiles/StyleProfileList'
import StyleProfileEditor from './pages/style-profiles/StyleProfileEditor'
import CitationList from './pages/citations/CitationList'
import BibliographyList from './pages/bibliography/BibliographyList'
import BibliographyDetail from './pages/bibliography/BibliographyDetail'
import AbbreviationList from './pages/abbreviations/AbbreviationList'
import QualityReport from './pages/reports/QualityReport'

import AppLayout from './components/layout/AppLayout'

function ProtectedRoute({ children }) {
  const token = useAuthStore((s) => s.token)
  if (!token) return <Navigate to="/login" replace />
  return children
}

function GuestRoute({ children }) {
  const token = useAuthStore((s) => s.token)
  if (token) return <Navigate to="/dashboard" replace />
  return children
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<GuestRoute><Login /></GuestRoute>} />
        <Route path="/register" element={<GuestRoute><Register /></GuestRoute>} />
        <Route path="/forgot-password" element={<GuestRoute><ForgotPassword /></GuestRoute>} />

        <Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/documents" element={<DocumentList />} />
          <Route path="/documents/upload" element={<DocumentUpload />} />
          <Route path="/documents/:id" element={<DocumentView />} />
          <Route path="/documents/:id/issues" element={<DocumentIssues />} />
          <Route path="/documents/:id/report" element={<QualityReport />} />
          <Route path="/documents/:id/citations" element={<CitationList />} />
          <Route path="/documents/:id/bibliography" element={<BibliographyList />} />
          <Route path="/documents/:id/bibliography/:entryId" element={<BibliographyDetail />} />
          <Route path="/documents/:id/abbreviations" element={<AbbreviationList />} />
          <Route path="/style-profiles" element={<StyleProfileList />} />
          <Route path="/style-profiles/:id/edit" element={<StyleProfileEditor />} />
        </Route>

        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
