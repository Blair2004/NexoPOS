<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { __m } from '../i18n';

declare const nsHttpClient: any;
declare const nsSnackBar: any;

const state = ref<any>({ model: 'gpt-5-mini', image_model: 'gpt-image-2', assistant_enabled: false, writes_enabled: false, daily_message_limit: 100, output_token_limit: 2000, context_token_limit: 12000, tool_call_limit: 8, configured: false });
const apiKey = ref('');
const loading = ref(true);
const saving = ref(false);
const testing = ref(false);

const api = (method: string, url: string, data?: any) => new Promise<any>((resolve, reject) => nsHttpClient[method](url, data).subscribe({ next: resolve, error: reject }));
const errorMessage = (error: any) => error?.message ?? error?.response?.data?.message ?? __m('Unable to complete the request.', 'NsOxen');

async function load(): Promise<void> {
    try { state.value = await api('get', '/api/oxen/admin/settings'); } catch (error) { nsSnackBar.error(errorMessage(error)); } finally { loading.value = false; }
}
async function save(): Promise<void> {
    saving.value = true;
    try { state.value = await api('put', '/api/oxen/admin/settings', { ...state.value, api_key: apiKey.value || undefined }); apiKey.value = ''; nsSnackBar.success(__m('Oxen settings saved.', 'NsOxen')); } catch (error) { nsSnackBar.error(errorMessage(error)); } finally { saving.value = false; }
}
async function testConnection(): Promise<void> {
    testing.value = true;
    try { await api('post', '/api/oxen/admin/connection-test', { api_key: apiKey.value || undefined, model: state.value.model }); nsSnackBar.success(__m('OpenAI connection succeeded.', 'NsOxen')); } catch (error) { nsSnackBar.error(errorMessage(error)); } finally { testing.value = false; }
}
onMounted(load);
</script>
<template>
    <section class="ox:mx-auto ox:max-w-3xl ox:space-y-6 ox:p-4 ox:text-fontcolor">
        <div v-if="loading"
            class="ox:min-h-32 ox:rounded-lg ox:border ox:border-box-edge ox:p-6 ox:text-fontcolor-soft">
                {{ __m('Loading settings…', 'NsOxen') }}
        </div>
        <form v-else class="ox:space-y-5" @submit.prevent="save">
            <div class="ox:rounded-lg ox:border ox:border-box-edge ox:bg-box-background ox:p-5 ox:space-y-4">
                <div><label class="ox:block ox:text-sm ox:font-medium" for="oxen-api-key">{{ __m('OpenAI API key', 'NsOxen') }}</label><input id="oxen-api-key" v-model="apiKey" type="password"
                        autocomplete="new-password"
                        class="ox:mt-1 ox:w-full ox:rounded-md ox:border ox:border-input-edge ox:bg-input-background ox:p-2"
                        :placeholder="state.configured ? __m('Configured — enter a new key to replace it', 'NsOxen') : 'sk-…'">
                    <p class="ox:mt-1 ox:text-xs ox:text-fontcolor-soft">{{ __m('The key is encrypted at rest and never returned to the browser.', 'NsOxen') }}</p>
                </div>
                <div><label class="ox:block ox:text-sm ox:font-medium" for="oxen-model">{{ __m('Model identifier', 'NsOxen') }}</label><input id="oxen-model" v-model="state.model" type="text"
                        class="ox:mt-1 ox:w-full ox:rounded-md ox:border ox:border-input-edge ox:bg-input-background ox:p-2">
                    <p class="ox:mt-1 ox:text-xs ox:text-fontcolor-soft">{{ __m('Use a model enabled for your OpenAI account.', 'NsOxen') }}</p>
                </div>
                <div><label class="ox:block ox:text-sm ox:font-medium" for="oxen-image-model">{{ __m('Image model identifier', 'NsOxen') }}</label><input id="oxen-image-model" v-model="state.image_model"
                        type="text"
                        class="ox:mt-1 ox:w-full ox:rounded-md ox:border ox:border-input-edge ox:bg-input-background ox:p-2">
                    <p class="ox:mt-1 ox:text-xs ox:text-fontcolor-soft">{{ __m('Used for approved product image generation; defaults to gpt-image-2.', 'NsOxen') }}</p>
                </div>
                <div class="ox:flex ox:flex-wrap ox:gap-6"><label class="ox:flex ox:items-center ox:gap-2"><input
                            v-model="state.assistant_enabled" type="checkbox">{{ __m('Enable assistant', 'NsOxen')
                        }}</label><label class="ox:flex ox:items-center ox:gap-2"><input v-model="state.writes_enabled"
                            type="checkbox">{{ __m('Enable write tools', 'NsOxen') }}</label></div>
            </div>
            <div class="ox:rounded-lg ox:border ox:border-box-edge ox:bg-box-background ox:p-5 ox:space-y-4">
                <h2 class="ox:font-medium">{{ __m('Usage limits', 'NsOxen') }}</h2>
                <div class="ox:grid ox:grid-cols-1 ox:gap-4 ox:sm:grid-cols-2"><label class="ox:text-sm">{{
                    __m('Messages per day', 'NsOxen') }}<input v-model.number="state.daily_message_limit"
                            type="number" min="1" max="1000"
                            class="ox:mt-1 ox:w-full ox:rounded-md ox:border ox:border-input-edge ox:bg-input-background ox:p-2"></label><label
                        class="ox:text-sm">{{ __m('Output tokens', 'NsOxen') }}<input
                            v-model.number="state.output_token_limit" type="number" min="128" max="8000"
                            class="ox:mt-1 ox:w-full ox:rounded-md ox:border ox:border-input-edge ox:bg-input-background ox:p-2"></label><label
                        class="ox:text-sm">{{ __m('Context tokens', 'NsOxen') }}<input
                            v-model.number="state.context_token_limit" type="number" min="1000" max="100000"
                            class="ox:mt-1 ox:w-full ox:rounded-md ox:border ox:border-input-edge ox:bg-input-background ox:p-2"></label><label
                        class="ox:text-sm" for="oxen-tool-call-limit">{{ __m('Tool calls per turn', 'NsOxen') }}<input
                            id="oxen-tool-call-limit" v-model.number="state.tool_call_limit" type="number" min="1"
                            max="64"
                            class="ox:mt-1 ox:w-full ox:rounded-md ox:border ox:border-input-edge ox:bg-input-background ox:p-2"><span
                            class="ox:mt-1 ox:block ox:text-xs ox:text-fontcolor-soft">{{ __m('Allows longer multi-step requests; higher values can increase response time and API usage.', 'NsOxen')
                            }}</span></label></div>
            </div>
            <div class="ox:flex ox:flex-wrap ox:justify-end ox:gap-3">
                <div class="ns-button default"><button type="button" class="ox:rounded-lg ox:px-4 ox:py-2"
                        :disabled="testing" @click="testConnection">{{ testing ? __m('Testing…', 'NsOxen') : __m('Test connection', 'NsOxen') }}</button></div>
                <div class="ns-button info"><button type="submit" class="ox:rounded-lg ox:px-4 ox:py-2"
                        :disabled="saving">{{ saving ? __m('Saving…', 'NsOxen') : __m('Save settings', 'NsOxen') }}</button></div>
            </div>
        </form>
    </section>
</template>
