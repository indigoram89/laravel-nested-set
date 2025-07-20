<template>
  <div class="nested-set-manager">
    <!-- Заголовок и управление -->
    <div class="mb-6 bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Управление деревьями</h1>
        <div class="flex items-center space-x-4">
          <ModelSelector 
            v-model="selectedModel" 
            :models="models"
            @change="loadTree"
          />
          <button 
            @click="showNodeForm = true"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Добавить узел
          </button>
        </div>
      </div>
      
      <!-- Поиск -->
      <div class="relative">
        <input 
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text" 
          placeholder="Поиск по дереву..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
        >
        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </div>
    </div>

    <!-- Дерево -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
      
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-600 mb-2">{{ error }}</div>
        <button @click="loadTree" class="text-blue-600 hover:text-blue-700">
          Попробовать снова
        </button>
      </div>
      
      <TreeView
        v-else-if="treeData.length > 0"
        :items="treeData"
        @edit="editNode"
        @delete="deleteNode"
        @reorder="handleReorder"
      />
      
      <div v-else class="text-center py-12 text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
        </svg>
        <p>Дерево пусто</p>
        <p class="text-sm mt-2">Нажмите "Добавить узел" чтобы создать первый элемент</p>
      </div>
    </div>

    <!-- Модальное окно формы -->
    <NodeForm
      v-if="showNodeForm"
      :node="editingNode"
      :model="selectedModel"
      :available-parents="availableParents"
      @save="saveNode"
      @close="closeNodeForm"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ModelSelector from './ModelSelector.vue';
import TreeView from './TreeView.vue';
import NodeForm from './NodeForm.vue';
import nestedSetApi from '../api/nestedSetApi';

// Состояние
const models = ref([]);
const selectedModel = ref('');
const treeData = ref([]);
const loading = ref(false);
const error = ref('');
const searchQuery = ref('');
const showNodeForm = ref(false);
const editingNode = ref(null);

// Вычисляемые свойства
const availableParents = computed(() => {
  const parents = [{ id: null, name: '-- Корневой элемент --', depth: -1 }];
  
  const flattenTree = (items, depth = 0) => {
    items.forEach(item => {
      // Исключаем редактируемый узел и его потомков
      if (!editingNode.value || item.id !== editingNode.value.id) {
        parents.push({
          id: item.id,
          name: item.name,
          depth: depth
        });
        
        if (item.children && item.children.length > 0) {
          flattenTree(item.children, depth + 1);
        }
      }
    });
  };
  
  flattenTree(treeData.value);
  return parents;
});

// Методы
const loadModels = async () => {
  try {
    const response = await nestedSetApi.getModels();
    models.value = response.data;
    
    if (models.value.length > 0 && !selectedModel.value) {
      selectedModel.value = models.value[0].name;
    }
  } catch (err) {
    error.value = 'Ошибка загрузки моделей: ' + err.message;
  }
};

const loadTree = async () => {
  if (!selectedModel.value) return;
  
  loading.value = true;
  error.value = '';
  
  try {
    const params = searchQuery.value ? { search: searchQuery.value } : {};
    const response = await nestedSetApi.getTree(selectedModel.value, params);
    treeData.value = response.data;
  } catch (err) {
    error.value = 'Ошибка загрузки дерева: ' + err.message;
  } finally {
    loading.value = false;
  }
};

const editNode = (node) => {
  editingNode.value = node;
  showNodeForm.value = true;
};

const deleteNode = async (node) => {
  if (!confirm(`Удалить узел "${node.name}" и все его дочерние элементы?`)) {
    return;
  }
  
  try {
    await nestedSetApi.deleteNode(selectedModel.value, node.id);
    await loadTree();
  } catch (err) {
    alert('Ошибка удаления: ' + err.message);
  }
};

const saveNode = async (formData) => {
  try {
    if (editingNode.value) {
      await nestedSetApi.updateNode(selectedModel.value, editingNode.value.id, formData);
    } else {
      await nestedSetApi.createNode(selectedModel.value, formData);
    }
    
    closeNodeForm();
    await loadTree();
  } catch (err) {
    alert('Ошибка сохранения: ' + err.message);
  }
};

const closeNodeForm = () => {
  showNodeForm.value = false;
  editingNode.value = null;
};

const handleReorder = async (items) => {
  try {
    await nestedSetApi.reorderNodes(selectedModel.value, items);
    await loadTree();
  } catch (err) {
    alert('Ошибка переупорядочивания: ' + err.message);
    await loadTree(); // Перезагружаем дерево в случае ошибки
  }
};

// Debounced поиск
let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadTree();
  }, 300);
};

// Жизненный цикл
onMounted(async () => {
  await loadModels();
  if (selectedModel.value) {
    await loadTree();
  }
});
</script>

<style scoped>
.nested-set-manager {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
</style>