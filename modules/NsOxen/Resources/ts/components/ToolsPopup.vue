<script setup lang="ts">
import { computed, ref } from 'vue';
import { __m } from '../i18n';

const props = defineProps<{ popup: any; tools: any[] }>();
const search = ref('');
const grouped = computed(() => {
    const query = search.value.trim().toLowerCase();
    const tools = props.tools.filter(tool => !query || `${tool.title} ${tool.description} ${tool.category} ${tool.source_module}`.toLowerCase().includes(query));
    return tools.reduce<Record<string, any[]>>((groups, tool) => {
        (groups[tool.category] ??= []).push(tool);
        return groups;
    }, {});
});
</script>

<template>
    <section class="ox:flex ox:max-h-[80vh] ox:w-[min(720px,calc(100vw-24px))] ox:flex-col ox:overflow-hidden ox:bg-box-background ox:text-fontcolor ox:shadow-lg">
        <header class="ox:flex ox:items-center ox:justify-between ox:border-b ox:border-box-edge ox:p-4">
            <div>
                <h2 class="ox:text-xl ox:font-semibold">{{ __m('Available tools', 'NsOxen') }}</h2>
                <p class="ox:text-sm ox:text-fontcolor-soft">{{ __m('Only tools you may use in this store are shown.', 'NsOxen') }}</p>
            </div>
            <button type="button" class="ox:rounded-lg ox:p-2 ox:hover:bg-box-elevation-hover" :aria-label="__m('Close tools', 'NsOxen')" @click="popup.close()">
                <i class="las la-times" aria-hidden="true"></i>
            </button>
        </header>
        <div class="ox:border-b ox:border-box-edge ox:p-4">
            <input v-model="search" type="search" class="ox:w-full ox:rounded-lg ox:border ox:border-input-edge ox:bg-input-background ox:p-3" :placeholder="__m('Search tools…', 'NsOxen')">
        </div>
        <div class="ox:flex ox:min-h-0 ox:flex-1 ox:flex-col ox:gap-5 ox:overflow-y-auto ox:p-4">
            <section v-for="(items, category) in grouped" :key="category">
                <h3 class="ox:mb-2 ox:font-semibold">{{ category }}</h3>
                <div class="ox:grid ox:gap-2 ox:md:grid-cols-2">
                    <article v-for="tool in items" :key="tool.name" class="ox:rounded-lg ox:border ox:border-box-edge ox:bg-box-elevation-background ox:p-3">
                        <div class="ox:flex ox:items-start ox:justify-between ox:gap-2">
                            <strong>{{ tool.title }}</strong>
                            <span class="ox:rounded-full ox:bg-info-secondary ox:px-2 ox:py-0.5 ox:text-xs ox:text-white">{{ tool.risk }}</span>
                        </div>
                        <p class="ox:mt-1 ox:text-sm ox:text-fontcolor-soft">{{ tool.description }}</p>
                        <p class="ox:mt-2 ox:text-xs ox:text-fontcolor-soft">{{ tool.source_module }} · {{ tool.requires_confirmation ? __m('Confirmation required', 'NsOxen') : __m('No extra confirmation', 'NsOxen') }}</p>
                    </article>
                </div>
            </section>
            <p v-if="Object.keys(grouped).length === 0" class="ox:text-center ox:text-fontcolor-soft">{{ __m('No matching tools.', 'NsOxen') }}</p>
        </div>
    </section>
</template>
