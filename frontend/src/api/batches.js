import api from './client'

export const listBatches = async () => {
  const { data } = await api.get('/batches')
  return data
}

export const createBatch = async (payload) => {
  const { data } = await api.post('/batches', payload)
  return data
}

export const getBatch = async (batchId) => {
  const { data } = await api.get(`/batches/${batchId}`)
  return data
}

export const getBatchItems = async (batchId) => {
  const { data } = await api.get(`/batches/${batchId}/items`)
  return data
}

export const exportBatch = async (batchId) => {
  const { data } = await api.post(`/batches/${batchId}/export`)
  return data
}

export const downloadBatchExport = async (batchId) => {
  return api.get(`/batches/${batchId}/export/download`, { responseType: 'blob' })
}
