<template>
    <div ref="draggable" class="ns-draggable-item" @mousedown="startDrag">
      <slot></slot>
    </div>
  </template>
  
  <script lang="ts">
  import { ref, onMounted, onUnmounted } from 'vue';

  declare const ns;
  
  export default {
    emits: ['drag-start', 'drag-end' ],
    name: 'ns-draggable',
    props: {
        widget: {
            required: true,
        },
    },
    setup( props, { emit}) {

        const draggable         = ref(null);
        const draggedElement    = ref(null);

        let dragGhost   =   null;

        let startX = 0;
        let startY = 0;
        let initialX = 0;
        let initialY = 0;
    
        const startDrag = (e) => {
            const wrapper   =   e.srcElement.closest( '.ns-draggable-item' );
            const position  =   wrapper.getBoundingClientRect();
            
            dragGhost = wrapper.cloneNode(true);
            dragGhost.setAttribute( 'class', 'ns-ghost' );
            dragGhost.style.display     =   'none';
            dragGhost.style.position    =   'fixed';
            dragGhost.style.top         =   `${position.top}px`;
            dragGhost.style.left        =   `${position.left}px`;
            dragGhost.style.width       =   `${position.width}px`;
            dragGhost.style.height      =   `${position.height}px`;

            const dropZone    =   wrapper.closest( '.ns-drop-zone' );
            
            dropZone.appendChild( dragGhost );

            startX = e.clientX - initialX;
            startY = e.clientY - initialY;
            
            draggedElement.value    =   {
                dom: wrapper,
            };

            emit( 'drag-start', props.widget );
        };
    
        const dragging = (e) => {
            if ( draggedElement.value === null ) {
                return;
            }

            const dragGhost = draggedElement.value.dom.closest( '.ns-drop-zone' ).querySelector( '.ns-ghost' );
            const firstChild = dragGhost.querySelector( 'div' );

            // Remove classes starting with "shadow"
            const classesToRemove = Array.from(firstChild.classList).filter(className => className.startsWith('shadow'));
            classesToRemove.forEach(className => firstChild.classList.remove(className));
            
            initialX = e.clientX - startX;
            initialY = e.clientY - startY;
            
            firstChild.style.boxShadow = '0px 4px 10px 5px rgb(0 0 0 / 48%)';

            dragGhost.style.display = 'block';
            dragGhost.style.transform = `translate(${initialX}px, ${initialY}px)`;   
            dragGhost.style.cursor = 'grabbing';

            const dropZones = document.querySelectorAll('.ns-drop-zone');
            dropZones.forEach((dropZone) => {
                const position = dropZone.getBoundingClientRect();

                const { left, top, right, bottom } = dropZone.getBoundingClientRect();
                const { clientX, clientY } = e;

                if (clientX >= left && clientX <= right && clientY >= top && clientY <= bottom) {
                    dropZone.setAttribute( 'hovered', 'true' );
                } else {
                    dropZone.setAttribute( 'hovered', 'false' );
                }
            }); 
        };
    
        const endDrag = (e ) => {
            if ( draggedElement.value === null ) {
                return;
            }

            const dropZone  =   draggedElement.value.dom.closest( '.ns-drop-zone' );
            const ghost     =   dropZone.querySelector( '.ns-ghost' );

            if ( ghost ) {
                ghost.remove();
            }

            draggedElement.value   =   null;

            initialX    = 0;
            initialY    = 0;
            const zone  =   e.srcElement.closest( '.ns-drop-zone' );

            emit( 'drag-end', props.widget );
        };
    
        onMounted(() => {
            if (draggable.value) {
                // draggable.value.style.position = 'absolute';
                document.addEventListener('mousemove', (e) => dragging(e));
                document.addEventListener('mouseup', (e) => endDrag( e ));
            }
        });
    
        onUnmounted(() => {
            document.removeEventListener('mousemove', dragging);
            document.removeEventListener('mouseup', endDrag);
        });
    
        return { draggable, startDrag };
    },
  };
  </script>
  
  <style>
  .ns-draggable-item {
    user-select: none;
  }
  .dark .ns-drop-zone[hovered="true"] {
    @apply border-slate-400;
  }
  .dark .ns-drop-zone[hovered="false"], .dark .ns-drop-zone:not([hovered]) {
    @apply border-transparent;
  }
  .light .ns-drop-zone[hovered="true"] {
    @apply border-slate-700;
  }
  .light .ns-drop-zone[hovered="false"], .light .ns-drop-zone:not([hovered]) {
    @apply border-transparent;
  }
  </style>