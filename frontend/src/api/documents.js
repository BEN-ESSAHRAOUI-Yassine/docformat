import api from './client'

export const listDocuments = async (projectId) => {
  const { data } = await api.get(`/projects/${projectId}/documents`)
  return data
}

export const getDocument = async (projectId, documentId) => {
  const { data } = await api.get(`/projects/${projectId}/documents/${documentId}`)
  return data
}

export const uploadDocument = async (projectId, file, onProgress) => {
  const formData = new FormData()
  formData.append('file', file)
  const { data } = await api.post(`/projects/${projectId}/documents`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    onUploadProgress: (e) => {
      if (onProgress && e.total) {
        onProgress(Math.round((e.loaded * 100) / e.total))
      }
    },
  })
  return data
}

export const deleteDocument = async (projectId, documentId) => {
  await api.delete(`/projects/${projectId}/documents/${documentId}`)
}

export const analyzeDocument = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/analyze`)
  return data
}

export const getAnalysis = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/analysis`)
  return data
}
