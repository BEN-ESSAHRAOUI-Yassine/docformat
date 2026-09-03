import api from './client'

export const getQuality = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/quality`)
  return data
}

export const getReport = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/report`)
  return data
}

export const generateReport = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/report/generate`)
  return data
}
