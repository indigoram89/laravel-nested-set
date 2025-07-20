<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div 
      class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
      @click="$emit('close')"
    ></div>
    
    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all">
        <!-- Header -->
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
        
        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="p-6">
          <!-- Name field -->
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
          
          <!-- Slug field -->
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
          
          <!-- Parent field -->
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
          
          <!-- Actions -->
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
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  node: {
    type: Object,
    default: null
  },
  model: {
    type: String,
    required: true
  },
  availableParents: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['save', 'close']);

// Данные формы
const formData = ref({
  name: props.node?.name || '',
  slug: props.node?.slug || '',
  parent_id: props.node?.parent_id || null
});

// Ошибки валидации
const errors = ref({});

// Вычисляемые свойства
const isValid = computed(() => {
  return formData.value.name.trim().length > 0 && Object.keys(errors.value).length === 0;
});

// Следим за изменениями имени для валидации
watch(() => formData.value.name, (newName) => {
  if (!newName.trim()) {
    errors.value.name = 'Название обязательно для заполнения';
  } else {
    delete errors.value.name;
  }
});

// Методы
const handleSubmit = () => {
  if (!isValid.value) return;
  
  const data = {
    name: formData.value.name.trim(),
    slug: formData.value.slug.trim() || null,
    parent_id: formData.value.parent_id
  };
  
  emit('save', data);
};

// Закрытие по Escape
const handleEscape = (e) => {
  if (e.key === 'Escape') {
    emit('close');
  }
};

// Жизненный цикл
window.addEventListener('keydown', handleEscape);
</script>

<style scoped>
/* Анимация появления модального окна */
.fixed {
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Анимация для содержимого */
.transform {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>