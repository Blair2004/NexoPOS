<template>
    <ns-page-title>
        <template #title>Labels</template>
        <template #description>creates a label selector fields.</template>
    </ns-page-title>
    <ns-tabs :active="active" @active="active = $event">
        <ns-tabs-item identifier="general" label="General">
            
        </ns-tabs-item>
        <ns-tabs-item identifier="demo" label="Demo">
            <ns-inline-multiselect 
                @change="( $event ) => console.log( $event )" 
                @blur="( $event ) => console.log( $event )" 
                :field="field">
            </ns-inline-multiselect>
            <div>
                {{ field }}
            </div>
        </ns-tabs-item>
    </ns-tabs>
</template>
<script>
import { nsInlineMultiselect } from '~/components/components';
export default {
    name: 'input-label',
    data() {
        return {
            console,
            active: 'demo',
            field: {
                label: 'Tag Selector',
                name: 'tag_selector',
                type: 'inline-multiselect',
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