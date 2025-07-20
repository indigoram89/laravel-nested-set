import { createApp } from 'vue';
import NestedSetManager from './components/NestedSetManager.vue';

// Создаём приложение Vue
const app = createApp({
    components: {
        NestedSetManager
    }
});

// Монтируем приложение
app.mount('#nested-set-app');