import axios from 'axios';

// Базовый URL для API
const API_BASE = '/api/nested-set';

// Настройка axios для включения CSRF токена
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

export default {
    /**
     * Получить список доступных моделей
     */
    async getModels() {
        const response = await axios.get(`${API_BASE}/models`);
        return response.data;
    },

    /**
     * Получить дерево для модели
     */
    async getTree(model, params = {}) {
        const response = await axios.get(`${API_BASE}/${model}/tree`, { params });
        return response.data;
    },

    /**
     * Создать новый узел
     */
    async createNode(model, data) {
        const response = await axios.post(`${API_BASE}/${model}/nodes`, data);
        return response.data;
    },

    /**
     * Обновить узел
     */
    async updateNode(model, id, data) {
        const response = await axios.put(`${API_BASE}/${model}/nodes/${id}`, data);
        return response.data;
    },

    /**
     * Удалить узел
     */
    async deleteNode(model, id) {
        const response = await axios.delete(`${API_BASE}/${model}/nodes/${id}`);
        return response.data;
    },

    /**
     * Переупорядочить узлы
     */
    async reorderNodes(model, items) {
        const response = await axios.post(`${API_BASE}/${model}/reorder`, { items });
        return response.data;
    }
};