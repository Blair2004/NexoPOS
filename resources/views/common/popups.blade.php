<div id="dashboard-popups">
    <div 
        :key="popup.hash" 
        v-for="(popup,key) of popups" 
        @click="closePopup( popup, $event )" 
        :id="popup.hash"
        :class="defaultClass">
        <div class="zoom-out-entrance popup-body" @click="preventPropagation( $event )">
            <component :popup="popup" :is="popup.component.value"></component>
        </div>    
    </div>
</div>
<!-- :data-index="key" -->
@vite([ 'resources/ts/popups.ts' ])