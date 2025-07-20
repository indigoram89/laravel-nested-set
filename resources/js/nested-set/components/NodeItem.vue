<template>
  <div class="node-item group">
    <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200">
      <!-- Drag handle -->
      <div class="drag-handle cursor-move mr-3 text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
        </svg>
      </div>
      
      <!-- Expand/Collapse button -->
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
      
      <!-- Node content -->
      <div class="flex-1">
        <div class="flex items-center">
          <span class="font-medium text-gray-800">{{ node.name }}</span>
          <span v-if="node.slug" class="ml-2 text-sm text-gray-500">
            ({{ node.slug }})
          </span>
        </div>
        
        <!-- Breadcrumb / Path -->
        <div v-if="showPath && node.depth > 0" class="text-xs text-gray-400 mt-1">
          <span v-for="i in node.depth" :key="i" class="mx-0.5">›</span>
          Уровень {{ node.depth }}
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
        <!-- Edit button -->
        <button 
          @click="$emit('edit')"
          class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"
          title="Редактировать"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
        </button>
        
        <!-- Delete button -->
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
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  node: {
    type: Object,
    required: true
  },
  showPath: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['edit', 'delete']);

// Локальное состояние развернутости
const isExpanded = ref(props.node.expanded !== false);

// Вычисляемые свойства
const hasChildren = computed(() => {
  return props.node.children && props.node.children.length > 0;
});

// Методы
const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value;
  // Обновляем свойство expanded в узле
  if (props.node.expanded !== undefined) {
    props.node.expanded = isExpanded.value;
  }
};

const confirmDelete = () => {
  const message = hasChildren.value 
    ? `Удалить узел "${props.node.name}" и все его дочерние элементы?`
    : `Удалить узел "${props.node.name}"?`;
    
  if (confirm(message)) {
    emit('delete');
  }
};
</script>

<style scoped>
.node-item {
  position: relative;
}

/* Hover эффекты */
.node-item:hover .drag-handle {
  opacity: 1;
}

/* Анимация для иконки разворачивания */
.rotate-90 {
  transform: rotate(90deg);
}

/* Стили для выделения при перетаскивании */
.node-item.sortable-ghost {
  opacity: 0.4;
}

.node-item.sortable-drag {
  cursor: move;
}
</style>