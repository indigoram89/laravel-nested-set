<template>
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
      
      <!-- Custom dropdown arrow -->
      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
    </div>
    
    <!-- Model description -->
    <p v-if="selectedModelInfo && selectedModelInfo.description" class="mt-1 text-sm text-gray-500">
      {{ selectedModelInfo.description }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  models: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

// Вычисляемые свойства
const selectedModelInfo = computed(() => {
  return props.models.find(m => m.name === props.modelValue);
});

// Методы
const handleChange = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value);
  emit('change', value);
};
</script>

<style scoped>
.model-selector {
  min-width: 200px;
}

/* Убираем стандартную стрелку в некоторых браузерах */
select::-ms-expand {
  display: none;
}

/* Hover эффект */
select:hover {
  border-color: #d1d5db;
}

/* Focus стили */
select:focus {
  outline: none;
}
</style>