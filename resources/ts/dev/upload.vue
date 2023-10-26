<template>
    <ns-page-title>
        <template #title>Labels</template>
        <template #description>creates a label selector fields.</template>
    </ns-page-title>
    <ns-tabs :active="active" @active="active = $event">
        <ns-tabs-item identifier="general" label="General">
            
        </ns-tabs-item>
        <ns-tabs-item identifier="demo" label="Demo">
            <ns-field :field="field"></ns-field>
            <div>
                {{ field }}
            </div>
        </ns-tabs-item>
    </ns-tabs>
</template>
<script>
import { nsMultiselect } from '~/components/components';
export default {
    name: 'input-label',
    data() {
        return {
            active: 'demo',
            field: {
                label: 'Upload',
                name: 'uploader',
                type: 'upload',
                value: '',
                options: [
                    {
                        label: 'Home',
                        value: 'home'
                    }, {
                        label: 'Bar',
                        value: 'bar'
                    }, {
                        label: 'Foo',
                        value: 'foo'
                    }, 
                ]
            }
        }
    }
}
</script>