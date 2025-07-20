<div class="nested-set-manager">
    <div class="mb-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <h2 class="text-2xl font-bold">Управление деревом</h2>
            <button wire:click="create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Добавить элемент
            </button>
        </div>
        <div>
            <input wire:model.live="search" type="text" placeholder="Поиск..." 
                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div x-data="nestedSetTree()" x-init="initTree(@js($tree_data))" class="nested-set-tree">
        <template x-if="items.length > 0">
            <ul x-sort="handleSort" x-sort:group="tree-root" class="tree-root space-y-2">
                <template x-for="item in items" :key="item.id">
                    <li x-sort:item="item" :data-id="item.id" class="tree-item">
                        <div class="flex items-center p-2 bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex-1 flex items-center">
                                <template x-if="item.children && item.children.length > 0">
                                    <button @click="toggleExpand(item)" class="mr-2 text-gray-500 hover:text-gray-700">
                                        <svg x-show="!item.expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        <svg x-show="item.expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                </template>
                                <template x-if="!item.children || item.children.length === 0">
                                    <span class="mr-2 w-4"></span>
                                </template>
                                <span class="font-medium" x-text="item.name"></span>
                                <span class="ml-2 text-sm text-gray-500" x-text="item.slug"></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button @click="$wire.edit(item.id)" class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button @click="if(confirm('Удалить элемент и все дочерние элементы?')) $wire.delete(item.id)" class="text-red-600 hover:text-red-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <template x-if="item.children && item.children.length > 0 && item.expanded">
                            <ul x-sort="handleSort" :x-sort:group="`tree-${item.id}`" class="ml-8 mt-2 space-y-2">
                                <template x-for="child in item.children" :key="child.id">
                                    <li x-sort:item="child" :data-id="child.id" class="tree-item">
                                        <div x-html="renderItem(child)"></div>
                                    </li>
                                </template>
                            </ul>
                        </template>
                    </li>
                </template>
            </ul>
        </template>
        <template x-if="items.length === 0">
            <div class="text-center py-8 text-gray-500">
                Нет элементов для отображения
            </div>
        </template>
    </div>

    <!-- Create Modal -->
    <div x-data="{ open: @entangle('show_create_modal') }" x-show="open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" 
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div x-show="open" x-transition:enter="ease-out duration-300" 
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form wire:submit.prevent="store">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Создать элемент</h3>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Название</label>
                            <input wire:model="name" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror">
                            @error('name') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Slug</label>
                            <input wire:model="slug" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <p class="text-gray-600 text-xs italic">Оставьте пустым для автоматической генерации</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Родительский элемент</label>
                            <select wire:model="parent_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Корневой элемент --</option>
                                @foreach($this->getModelInstance()::all() as $item)
                                    <option value="{{ $item->id }}">{{ str_repeat('—', $item->depth) }} {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Создать
                        </button>
                        <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-data="{ open: @entangle('show_edit_modal') }" x-show="open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" 
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div x-show="open" x-transition:enter="ease-out duration-300" 
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form wire:submit.prevent="update">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Редактировать элемент</h3>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Название</label>
                            <input wire:model="name" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror">
                            @error('name') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Slug</label>
                            <input wire:model="slug" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Родительский элемент</label>
                            <select wire:model="parent_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Корневой элемент --</option>
                                @foreach($this->getModelInstance()::all() as $item)
                                    @if($item->id !== $editing_id)
                                        <option value="{{ $item->id }}">{{ str_repeat('—', $item->depth) }} {{ $item->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Сохранить
                        </button>
                        <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function nestedSetTree() {
    return {
        items: [],
        
        initTree(data) {
            this.items = data;
        },
        
        toggleExpand(item) {
            item.expanded = !item.expanded;
        },
        
        handleSort(event) {
            const movedItem = event.detail.item;
            const newContainer = event.detail.newContainer;
            const newIndex = event.detail.newIndex;
            
            this.updateTreeStructure();
            this.$wire.reorder(this.getTreeData());
        },
        
        updateTreeStructure() {
            // Tree structure is automatically updated by Alpine Sort
        },
        
        getTreeData() {
            return this.extractTreeData(this.items);
        },
        
        extractTreeData(items) {
            return items.map(item => {
                const data = {
                    id: item.id
                };
                
                if (item.children && item.children.length > 0) {
                    data.children = this.extractTreeData(item.children);
                }
                
                return data;
            });
        },
        
        renderItem(item) {
            let html = `
                <div class="flex items-center p-2 bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex-1 flex items-center">`;
            
            if (item.children && item.children.length > 0) {
                html += `
                    <button @click="toggleExpand(${JSON.stringify(item).replace(/"/g, '&quot;')})" class="mr-2 text-gray-500 hover:text-gray-700">
                        <svg x-show="!${item.expanded}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <svg x-show="${item.expanded}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>`;
            } else {
                html += '<span class="mr-2 w-4"></span>';
            }
            
            html += `
                        <span class="font-medium">${item.name}</span>
                        <span class="ml-2 text-sm text-gray-500">${item.slug || ''}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="$wire.edit(${item.id})" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button @click="if(confirm('Удалить элемент и все дочерние элементы?')) $wire.delete(${item.id})" class="text-red-600 hover:text-red-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>`;
            
            if (item.children && item.children.length > 0 && item.expanded) {
                html += `
                <ul x-sort="handleSort" x-sort:group="tree-${item.id}" class="ml-8 mt-2 space-y-2">`;
                
                item.children.forEach(child => {
                    html += `
                    <li x-sort:item='${JSON.stringify(child).replace(/'/g, '&apos;')}' data-id="${child.id}" class="tree-item">
                        ${this.renderItem(child)}
                    </li>`;
                });
                
                html += '</ul>';
            }
            
            return html;
        }
    }
}
</script>
@endpush