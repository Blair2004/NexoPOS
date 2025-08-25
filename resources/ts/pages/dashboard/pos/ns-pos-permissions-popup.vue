<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
      <!-- Header -->
      <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">
          {{ __('Permission Required') }}
        </h2>
        <p class="text-gray-600">
          {{ __('This action requires elevated permissions. Please request access from an administrator.') }}
        </p>
      </div>

      <!-- Permission Details -->
      <div class="mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
          <h3 class="font-semibold text-blue-800 mb-2">{{ __('Required Permission') }}</h3>
          <p class="text-blue-700 text-sm font-mono bg-blue-100 px-2 py-1 rounded">
            {{ permissionName }}
          </p>
        </div>
        
        <div class="text-sm text-gray-600">
          <!-- <p><strong>{{ __('Action:') }}</strong> {{ actionDescription }}</p>
          <p><strong>{{ __('User:') }}</strong> {{ currentUser.username }}</p>
          <p><strong>{{ __('Time:') }}</strong> {{ currentTime }}</p> -->
        </div>
      </div>

      <!-- Request Methods -->
      <div class="space-y-4">
        <!-- QR Code Section -->
        <div v-if="showQRCode" class="text-center">
          <h3 class="font-semibold text-gray-800 mb-3">{{ __('Scan QR Code') }}</h3>
          <div class="bg-white p-4 rounded-lg border-2 border-gray-200 inline-block">
            <canvas ref="qrCanvas" class="w-32 h-32 mx-auto"></canvas>
          </div>
          <p class="text-xs text-gray-500 mt-2">
            {{ __('Admin can scan this code to grant temporary access') }}
          </p>
        </div>

        <!-- Divider -->
        <div class="flex items-center justify-center">
          <div class="border-t border-gray-300 flex-grow"></div>
          <span class="px-4 text-sm text-gray-500">{{ __('OR') }}</span>
          <div class="border-t border-gray-300 flex-grow"></div>
        </div>

        <!-- Admin Credentials Form -->
        <div>
          <h3 class="font-semibold text-gray-800 mb-3">{{ __('Admin Credentials') }}</h3>
          <form @submit.prevent="submitCredentials" class="space-y-3">
            <div>
              <label for="adminUsername" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('Username') }}
              </label>
              <input
                id="adminUsername"
                v-model="credentials.username"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :placeholder="__('Enter admin username')"
                required
              >
            </div>
            <div>
              <label for="adminPassword" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('Password') }}
              </label>
              <input
                id="adminPassword"
                v-model="credentials.password"
                type="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :placeholder="__('Enter admin password')"
                required
              >
            </div>
            <div class="flex items-center">
              <input
                id="temporaryAccess"
                v-model="credentials.temporary"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              >
              <label for="temporaryAccess" class="ml-2 block text-sm text-gray-900">
                {{ __('Grant temporary access (5 minutes)') }}
              </label>
            </div>
          </form>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
        <p class="text-sm text-red-800">{{ errorMessage }}</p>
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
        <button
          @click="submitCredentials"
          :disabled="isLoading || !credentials.username || !credentials.password"
          class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
          <span v-if="!isLoading">{{ __('Grant Access') }}</span>
          <span v-else class="flex items-center justify-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ __('Verifying...') }}
          </span>
        </button>
        <button
          @click="$emit('close')"
          class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
        >
          {{ __('Cancel') }}
        </button>
      </div>

      <!-- Help Text -->
      <div class="mt-4 text-center">
        <p class="text-xs text-gray-500">
          {{ __('Contact your system administrator if you need regular access to this feature.') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { __ } from '~/libraries/lang'
import { nsSnackBar } from '~/bootstrap'
import QRCode from 'qrcode'

interface Props {
  permissionName: string
  actionDescription: string
  currentUser: {
    id: number
    username: string
  }
}

interface Credentials {
  username: string
  password: string
  temporary: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  granted: [permissions: string[]]
}>()

// Reactive state
const credentials = ref<Credentials>({
  username: '',
  password: '',
  temporary: true
})

const errorMessage = ref('')
const isLoading = ref(false)
const showQRCode = ref(true)
const qrCanvas = ref<HTMLCanvasElement>()

// Computed properties
const currentTime = computed(() => {
  return new Date().toLocaleString()
})

const qrData = computed(() => {
  return JSON.stringify({
    type: 'permission_request',
    permission: props.permissionName,
    user_id: props.currentUser.id,
    username: props.currentUser.username,
    timestamp: Date.now(),
    action: props.actionDescription
  })
})

// Methods
const generateQRCode = async () => {
  if (!qrCanvas.value) return
  
  try {
    await QRCode.toCanvas(qrCanvas.value, qrData.value, {
      width: 128,
      margin: 1,
      color: {
        dark: '#1f2937',
        light: '#ffffff'
      }
    })
  } catch (error) {
    console.error('Failed to generate QR code:', error)
    showQRCode.value = false
  }
}

const submitCredentials = async () => {
  if (isLoading.value) return
  
  errorMessage.value = ''
  isLoading.value = true
  
  try {
    const response = await fetch('/api/pos/request-permission', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        permission: props.permissionName,
        admin_username: credentials.value.username,
        admin_password: credentials.value.password,
        temporary: credentials.value.temporary,
        user_id: props.currentUser.id
      })
    })
    
    const data = await response.json()
    
    if (response.ok && data.success) {
      nsSnackBar.success(__('Permission granted successfully'));
      emit('granted', [props.permissionName])
    } else {
      errorMessage.value = data.message || __('Invalid credentials or insufficient permissions')
    }
  } catch (error) {
    console.error('Permission request failed:', error)
    errorMessage.value = __('Failed to process request. Please try again.')
  } finally {
    isLoading.value = false
  }
}

// Lifecycle
onMounted(() => {
  generateQRCode()
})
</script>

<style scoped>
/* Additional responsive styles for mobile */
@media (max-width: 640px) {
  .max-w-md {
    max-width: calc(100vw - 2rem);
  }
}

/* Focus styles for accessibility */
input:focus,
button:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Loading animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
