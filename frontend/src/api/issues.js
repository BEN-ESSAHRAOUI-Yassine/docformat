import api from './client'

export const getIssues = async (documentId, params = {}) => {
  const { data } = await api.get(`/documents/${documentId}/issues`, { params })
  return data
}

export const acceptIssue = async (documentId, issueId) => {
  const { data } = await api.post(`/documents/${documentId}/issues/${issueId}/accept`)
  return data
}

export const rejectIssue = async (documentId, issueId) => {
  const { data } = await api.post(`/documents/${documentId}/issues/${issueId}/reject`)
  return data
}

export const editIssue = async (documentId, issueId, recommendation) => {
  const { data } = await api.post(`/documents/${documentId}/issues/${issueId}/edit`, { recommendation })
  return data
}

export const ignoreIssue = async (documentId, issueId, reason) => {
  const { data } = await api.post(`/documents/${documentId}/issues/${issueId}/ignore`, { reason })
  return data
}

export const bulkDecideIssues = async (documentId, payload) => {
  const { data } = await api.post(`/documents/${documentId}/issues/bulk`, payload)
  return data
}
