import api from './client'

export const exportDocument = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/export`)
  return data
}

export const getDownloadUrl = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/download`)
  return data
}

export const downloadExport = async (documentId) => {
  return api.get(`/documents/${documentId}/download/stream`, { responseType: 'blob' })
}
