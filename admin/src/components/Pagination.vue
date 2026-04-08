<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  currentPage: number
  lastPage: number
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

const pages = computed(() => {
  const total = props.lastPage
  const current = props.currentPage
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const items: (number | '...')[] = [1]
  const left = Math.max(2, current - 1)
  const right = Math.min(total - 1, current + 1)

  if (left > 2) items.push('...')
  for (let i = left; i <= right; i++) items.push(i)
  if (right < total - 1) items.push('...')
  if (total > 1) items.push(total)

  return items
})
</script>

<template>
  <div v-if="lastPage > 1" class="flex items-center justify-center gap-1">
    <button
      :disabled="currentPage <= 1"
      @click="emit('change', currentPage - 1)"
      class="flex h-8 items-center rounded px-2 text-xs text-gray-400 transition hover:bg-gray-800 hover:text-white disabled:pointer-events-none disabled:opacity-30"
    >
      &laquo;
    </button>

    <template v-for="(p, idx) in pages" :key="idx">
      <span v-if="p === '...'" class="px-1 text-xs text-gray-600">...</span>
      <button
        v-else
        @click="emit('change', p)"
        :class="[
          'h-8 w-8 rounded text-xs transition',
          p === currentPage
            ? 'bg-amber-500 font-bold text-black'
            : 'bg-gray-800 text-gray-400 hover:bg-gray-700'
        ]"
      >{{ p }}</button>
    </template>

    <button
      :disabled="currentPage >= lastPage"
      @click="emit('change', currentPage + 1)"
      class="flex h-8 items-center rounded px-2 text-xs text-gray-400 transition hover:bg-gray-800 hover:text-white disabled:pointer-events-none disabled:opacity-30"
    >
      &raquo;
    </button>
  </div>
</template>
