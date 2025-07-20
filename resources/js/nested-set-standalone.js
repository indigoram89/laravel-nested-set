// Standalone версия для composer пакета
// Включает Vue 3, axios и все компоненты в одном файле

(function() {
    'use strict';

    // API клиент
    const nestedSetApi = {
        apiBase: '/api/nested-set',
        
        async request(method, url, data = null) {
            const token = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
            
            if (token) {
                headers['X-CSRF-TOKEN'] = token.content;
            }
            
            const config = {
                method,
                headers,
                credentials: 'same-origin'
            };
            
            if (data && method !== 'GET') {
                config.body = JSON.stringify(data);
            }
            
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        },
        
        async getModels() {
            return this.request('GET', `${this.apiBase}/models`);
        },
        
        async getTree(model, params = {}) {
            const query = new URLSearchParams(params).toString();
            const url = `${this.apiBase}/${model}/tree${query ? '?' + query : ''}`;
            return this.request('GET', url);
        },
        
        async createNode(model, data) {
            return this.request('POST', `${this.apiBase}/${model}/nodes`, data);
        },
        
        async updateNode(model, id, data) {
            return this.request('PUT', `${this.apiBase}/${model}/nodes/${id}`, data);
        },
        
        async deleteNode(model, id) {
            return this.request('DELETE', `${this.apiBase}/${model}/nodes/${id}`);
        },
        
        async reorderNodes(model, items) {
            return this.request('POST', `${this.apiBase}/${model}/reorder`, { items });
        }
    };

    // Ждём загрузки Vue из CDN
    function waitForVue(callback) {
        if (window.Vue && window.Sortable) {
            callback();
        } else {
            setTimeout(() => waitForVue(callback), 100);
        }
    }

    waitForVue(() => {
        const { createApp, ref, computed, watch, onMounted, onUnmounted } = Vue;

        // Компонент ModelSelector
        const ModelSelector = {
            props: ['modelValue', 'models'],
            emits: ['update:modelValue', 'change'],
            template: `
                <div class="model-selector">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Модель
                    </label>
                    <div class="relative">
                        <select
                            :value="modelValue"
                            @change="handleChange"
                            class="block w-full px-4 py-2 pr-8 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none"
                        >
                            <option value="" disabled>Выберите модель</option>
                            <option 
                                v-for="model in models" 
                                :key="model.name"
                                :value="model.name"
                            >
                                {{ model.label || model.name }}
                            </option>
                        </select>
                        
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <p v-if="selectedModelInfo && selectedModelInfo.description" class="mt-1 text-sm text-gray-500">
                        {{ selectedModelInfo.description }}
                    </p>
                </div>
            `,
            setup(props, { emit }) {
                const selectedModelInfo = computed(() => {
                    return props.models.find(m => m.name === props.modelValue);
                });
                
                const handleChange = (event) => {
                    const value = event.target.value;
                    emit('update:modelValue', value);
                    emit('change', value);
                };
                
                return { selectedModelInfo, handleChange };
            }
        };

        // Компонент NodeItem
        const NodeItem = {
            props: ['node', 'showPath'],
            emits: ['edit', 'delete'],
            template: `
                <div class="node-item group">
                    <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200">
                        <div class="drag-handle cursor-move mr-3 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>
                        
                        <button 
                            v-if="hasChildren"
                            @click="toggleExpanded"
                            class="mr-2 p-1 text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            <svg 
                                class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-90': isExpanded }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <div v-else class="w-8"></div>
                        
                        <div class="flex-1">
                            <div class="flex items-center">
                                <span class="font-medium text-gray-800">{{ node.name }}</span>
                                <span v-if="node.slug" class="ml-2 text-sm text-gray-500">
                                    ({{ node.slug }})
                                </span>
                            </div>
                            
                            <div v-if="showPath && node.depth > 0" class="text-xs text-gray-400 mt-1">
                                <span v-for="i in node.depth" :key="i" class="mx-0.5">›</span>
                                Уровень {{ node.depth }}
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <button 
                                @click="$emit('edit')"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                title="Редактировать"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            
                            <button 
                                @click="confirmDelete"
                                class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                                title="Удалить"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `,
            setup(props, { emit }) {
                // Создаём реактивную ссылку на узел
                const node = ref(props.node);
                
                // Инициализируем expanded на основе свойства узла
                const isExpanded = computed({
                    get: () => node.value.expanded !== false,
                    set: (value) => {
                        node.value.expanded = value;
                    }
                });
                
                const hasChildren = computed(() => {
                    return node.value.children && node.value.children.length > 0;
                });
                
                const toggleExpanded = () => {
                    isExpanded.value = !isExpanded.value;
                };
                
                const confirmDelete = () => {
                    const message = hasChildren.value 
                        ? `Удалить узел "${node.value.name}" и все его дочерние элементы?`
                        : `Удалить узел "${node.value.name}"?`;
                        
                    if (confirm(message)) {
                        emit('delete');
                    }
                };
                
                return { isExpanded, hasChildren, toggleExpanded, confirmDelete };
            }
        };

        // Компонент TreeView (упрощённый без vuedraggable)
        const TreeView = {
            name: 'TreeView',
            props: ['items'],
            emits: ['edit', 'delete', 'reorder'],
            components: { NodeItem },
            template: `
                <div class="tree-view">
                    <ul class="tree-list" ref="treeList">
                        <li v-for="item in items" :key="item.id" class="tree-node" :data-id="item.id">
                            <node-item
                                :node="item"
                                @edit="$emit('edit', item)"
                                @delete="$emit('delete', item)"
                            />
                            
                            <div 
                                v-if="item.children && item.children.length > 0 && item.expanded !== false"
                                class="ml-8 mt-2"
                            >
                                <tree-view
                                    :items="item.children"
                                    @edit="$emit('edit', $event)"
                                    @delete="$emit('delete', $event)"
                                    @reorder="$emit('reorder', $event)"
                                />
                            </div>
                        </li>
                    </ul>
                </div>
            `,
            mounted() {
                // Простая реализация drag & drop
                this.$nextTick(() => {
                    this.initDragAndDrop();
                });
            },
            updated() {
                // Переинициализируем при обновлении
                this.$nextTick(() => {
                    this.initDragAndDrop();
                });
            },
            methods: {
                initDragAndDrop() {
                    if (window.Sortable && this.$refs.treeList) {
                        this.initSortableForList(this.$refs.treeList);
                    }
                },
                initSortableForList(listElement) {
                    new window.Sortable(listElement, {
                        group: 'nested',
                        handle: '.drag-handle',
                        animation: 150,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        onEnd: (evt) => {
                            // Ждём немного чтобы DOM обновился
                            setTimeout(() => {
                                this.handleReorder();
                            }, 100);
                        }
                    });
                    
                    // Инициализируем sortable для всех вложенных списков
                    const nestedLists = listElement.querySelectorAll('.tree-list');
                    nestedLists.forEach(list => {
                        if (list !== listElement) {
                            this.initSortableForList(list);
                        }
                    });
                },
                handleReorder() {
                    // Собираем структуру всего дерева начиная с корня
                    const getRootTreeView = () => {
                        let current = this;
                        while (current.$parent && current.$parent.$options.name === 'TreeView') {
                            current = current.$parent;
                        }
                        return current;
                    };
                    
                    const rootTreeView = getRootTreeView();
                    
                    const extractStructure = (container) => {
                        const items = [];
                        const nodes = container.querySelectorAll(':scope > .tree-node');
                        
                        nodes.forEach(node => {
                            const id = parseInt(node.dataset.id);
                            const item = { id };
                            
                            const childContainer = node.querySelector(':scope > div > .tree-view > .tree-list');
                            if (childContainer && childContainer.children.length > 0) {
                                item.children = extractStructure(childContainer);
                            }
                            
                            items.push(item);
                        });
                        
                        return items;
                    };
                    
                    const structure = extractStructure(rootTreeView.$refs.treeList);
                    this.$emit('reorder', structure);
                }
            }
        };

        // Компонент NodeForm
        const NodeForm = {
            props: ['node', 'model', 'availableParents'],
            emits: ['save', 'close'],
            template: `
                <div class="fixed inset-0 z-50 overflow-y-auto">
                    <div 
                        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                        @click="$emit('close')"
                    ></div>
                    
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ node ? 'Редактировать узел' : 'Создать узел' }}
                                </h3>
                                <button
                                    @click="$emit('close')"
                                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <form @submit.prevent="handleSubmit" class="p-6">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Название <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="formData.name"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="{ 'border-red-500': errors.name }"
                                        placeholder="Введите название узла"
                                    >
                                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Slug
                                    </label>
                                    <input
                                        v-model="formData.slug"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Оставьте пустым для автогенерации"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">URL-дружественный идентификатор</p>
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Родительский элемент
                                    </label>
                                    <select
                                        v-model="formData.parent_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option 
                                            v-for="parent in availableParents" 
                                            :key="parent.id" 
                                            :value="parent.id"
                                        >
                                            {{ '—'.repeat(parent.depth + 1) }} {{ parent.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="$emit('close')"
                                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                                    >
                                        Отмена
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="!isValid"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        {{ node ? 'Сохранить' : 'Создать' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `,
            setup(props, { emit }) {
                const formData = ref({
                    name: props.node?.name || '',
                    slug: props.node?.slug || '',
                    parent_id: props.node?.parent_id || null
                });
                
                const errors = ref({});
                
                const isValid = computed(() => {
                    return formData.value.name.trim().length > 0 && Object.keys(errors.value).length === 0;
                });
                
                watch(() => formData.value.name, (newName) => {
                    if (!newName.trim()) {
                        errors.value.name = 'Название обязательно для заполнения';
                    } else {
                        delete errors.value.name;
                    }
                });
                
                const handleSubmit = () => {
                    if (!isValid.value) return;
                    
                    const data = {
                        name: formData.value.name.trim(),
                        slug: formData.value.slug.trim() || null,
                        parent_id: formData.value.parent_id
                    };
                    
                    emit('save', data);
                };
                
                return { formData, errors, isValid, handleSubmit };
            }
        };

        // Главный компонент
        const NestedSetManager = {
            components: { ModelSelector, TreeView, NodeForm },
            template: `
                <div class="nested-set-manager">
                    <div class="mb-6 bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h1 class="text-2xl font-bold text-gray-800">Управление деревьями</h1>
                            <div v-if="models.length > 0" class="flex items-center space-x-4">
                                <model-selector 
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
                        
                        <div v-if="models.length > 0" class="relative">
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

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div v-if="models.length === 0" class="text-center py-12">
                            <div class="max-w-2xl mx-auto">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Модели не настроены</h3>
                                <p class="text-gray-500 mb-4">Для начала работы необходимо настроить модели в конфигурационном файле.</p>
                                <div class="bg-gray-50 rounded-lg p-4 text-left">
                                    <p class="text-sm text-gray-700 mb-2">1. Опубликуйте конфигурацию:</p>
                                    <code class="block bg-gray-800 text-gray-100 p-2 rounded text-xs mb-3">php artisan vendor:publish --tag=nested-set-config</code>
                                    
                                    <p class="text-sm text-gray-700 mb-2">2. Добавьте модели в config/nested-set.php:</p>
                                    <pre class="bg-gray-800 text-gray-100 p-2 rounded text-xs overflow-x-auto">'models' => [
    [
        'name' => 'category',
        'class' => App\\Models\\Category::class,
        'label' => 'Категории',
    ],
],</pre>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else-if="loading" class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>
                        
                        <div v-else-if="error" class="text-center py-12">
                            <div class="text-red-600 mb-2">{{ error }}</div>
                            <button @click="loadTree" class="text-blue-600 hover:text-blue-700">
                                Попробовать снова
                            </button>
                        </div>
                        
                        <tree-view
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

                    <node-form
                        v-if="showNodeForm"
                        :node="editingNode"
                        :model="selectedModel"
                        :available-parents="availableParents"
                        @save="saveNode"
                        @close="closeNodeForm"
                    />
                </div>
            `,
            setup() {
                const models = ref([]);
                const selectedModel = ref('');
                const treeData = ref([]);
                const loading = ref(false);
                const error = ref('');
                const searchQuery = ref('');
                const showNodeForm = ref(false);
                const editingNode = ref(null);
                
                const availableParents = computed(() => {
                    const parents = [{ id: null, name: '-- Корневой элемент --', depth: -1 }];
                    
                    const flattenTree = (items, depth = 0) => {
                        items.forEach(item => {
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
                
                const loadModels = async () => {
                    try {
                        const response = await nestedSetApi.getModels();
                        models.value = response.data;
                        
                        if (models.value.length > 0 && !selectedModel.value) {
                            selectedModel.value = models.value[0].name;
                        } else if (models.value.length === 0) {
                            error.value = 'Модели не настроены. Добавьте модели в config/nested-set.php';
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
                        await loadTree();
                    }
                };
                
                let searchTimeout = null;
                const debouncedSearch = () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        loadTree();
                    }, 300);
                };
                
                onMounted(async () => {
                    await loadModels();
                    if (selectedModel.value) {
                        await loadTree();
                    }
                });
                
                return {
                    models,
                    selectedModel,
                    treeData,
                    loading,
                    error,
                    searchQuery,
                    showNodeForm,
                    editingNode,
                    availableParents,
                    loadTree,
                    editNode,
                    deleteNode,
                    saveNode,
                    closeNodeForm,
                    handleReorder,
                    debouncedSearch
                };
            }
        };

        // Создаём и монтируем приложение
        const app = createApp({
            components: {
                NestedSetManager
            }
        });
        
        // Регистрируем TreeView глобально для рекурсивного использования
        app.component('TreeView', TreeView);

        app.mount('#nested-set-app');
    });
})();