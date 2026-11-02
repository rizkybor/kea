<script setup lang="ts">
/** Input daftar tag (skill, kualifikasi). Enter atau koma menambah tag. */
const model = defineModel<string[]>({ required: true })
const props = withDefaults(defineProps<{ placeholder?: string; max?: number; id?: string }>(), { max: 15 })
const draft = ref('')

function add() {
  const value = draft.value.trim().replace(/,$/, '')
  if (value && !model.value.some(t => t.toLowerCase() === value.toLowerCase()) && model.value.length < props.max) {
    model.value = [...model.value, value]
  }
  draft.value = ''
}
function remove(i: number) {
  model.value = model.value.filter((_, idx) => idx !== i)
}
</script>

<template>
  <div class="input flex min-h-11 flex-wrap items-center gap-1.5 py-1.5">
    <span v-for="(tag, i) in model" :key="tag" class="inline-flex items-center gap-1 rounded-md bg-kea-olive-50 py-0.5 pr-1 pl-2 text-sm font-semibold text-kea-olive-900">
      {{ tag }}
      <button type="button" class="flex size-5 items-center justify-center rounded hover:bg-kea-olive/30" :aria-label="`Hapus ${tag}`" @click="remove(i)">×</button>
    </span>
    <input
      :id="id"
      v-model="draft"
      :placeholder="model.length ? '' : placeholder"
      class="min-w-[120px] flex-1 border-0 bg-transparent py-1 text-[15px] font-normal outline-none"
      @keydown.enter.prevent="add"
      @keydown.,.prevent="add"
      @keydown.backspace="!draft && model.length && remove(model.length - 1)"
      @blur="add"
    >
  </div>
</template>
