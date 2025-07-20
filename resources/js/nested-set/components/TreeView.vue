<template>
  <div class="tree-view">
    <draggable
      v-model="localItems"
      group="tree"
      item-key="id"
      handle=".drag-handle"
      :animation="200"
      @change="handleChange"
      tag="ul"
      class="tree-list"
    >
      <template #item="{ element }">
        <li class="tree-node" :data-id="element.id">
          <NodeItem
            :node="element"
            @edit="$emit('edit', element)"
            @delete="$emit('delete', element)"
          />
          
          <!-- Рекурсивный вызов для дочерних элементов -->
          <TreeView
            v-if="element.children && element.children.length > 0 && element.expanded !== false"
            :items="element.children"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
            @reorder="handleChildReorder"
            class="ml-8 mt-2"
          />
        </li>
      </template>
    </draggable>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import NodeItem from './NodeItem.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['edit', 'delete', 'reorder']);

// Локальная копия items для работы с draggable
const localItems = ref([...props.items]);

// Следим за изменениями props.items
watch(() => props.items, (newItems) => {
  localItems.value = [...newItems];
}, { deep: true });

// Обработка изменений порядка
const handleChange = (evt) => {
  // Извлекаем структуру дерева для отправки
  const extractTreeStructure = (items) => {
    return items.map(item => {
      const node = { id: item.id };
      if (item.children && item.children.length > 0) {
        node.children = extractTreeStructure(item.children);
      }
      return node;
    });
  };
  
  emit('reorder', extractTreeStructure(localItems.value));
};

// Обработка изменений в дочерних элементах
const handleChildReorder = (items) => {
  emit('reorder', items);
};
</script>

<style scoped>
.tree-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.tree-node {
  margin-bottom: 0.5rem;
}

/* Стили для перетаскивания */
.sortable-ghost {
  opacity: 0.5;
}

.sortable-drag {
  background-color: #f3f4f6;
  border-radius: 0.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Индикатор места для вставки */
.sortable-chosen {
  background-color: #eff6ff;
}
</style>