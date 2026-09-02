import { useState } from 'react'
import { Link } from 'react-router-dom'
import { forgotPassword as apiForgotPassword } from '../../api/auth'

export default function ForgotPassword() {
  const [email, setEmail] = useState('')
  const [sent, setSent] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await apiForgotPassword(email)
      setSent(true)
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to send reset link')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50">
      <div className="w-full max-w-md p-8 bg-white rounded-lg shadow">
        <h1 className="text-2xl font-semibold text-slate-900 mb-6">Reset password</h1>
        {sent ? (
          <div className="text-center">
            <p className="text-slate-600 mb-4">
              Check your email for a password reset link.
            </p>
            <Link to="/login" className="text-blue-600 hover:underline">Back to sign in</Link>
          </div>
        ) : (
          <>
            {error && (
              <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                {error}
              </div>
            )}
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  className="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <button
                type="submit"
                disabled={loading}
                className="w-full py-2 px-4 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 disabled:opacity-50"
              >
                {loading ? 'Sending...' : 'Send reset link'}
              </button>
            </form>
          </>
        )}
        <p className="mt-4 text-sm text-center text-slate-500">
          <Link to="/login" className="text-blue-600 hover:underline">Back to sign in</Link>
        </p>
      </div>
    </div>
  )
}
