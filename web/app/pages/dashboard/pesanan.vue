<script setup lang="ts">
import type { Order } from '~/types/api'
import { rupiah, formatDate } from '~/utils/format'

definePageMeta({ layout: 'learner', middleware: 'auth' })
const api = useApi()
const { data } = await useAsyncData('my-orders', () => api<{ data: Order[] }>('/orders'))
const label: Record<Order['status'], string> = { pending: 'Menunggu bayar', paid: 'Lunas', expired: 'Kedaluwarsa', failed: 'Gagal', refunded: 'Refund' }
useSeoMeta({ title: 'Pesanan', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[900px] flex-col gap-5">
    <h1 class="text-[28px] font-extrabold">Pesanan</h1>
    <div class="card overflow-x-auto">
      <table class="w-full min-w-[560px] text-left text-[15px]">
        <thead class="border-b border-kea-line text-sm text-kea-muted">
          <tr><th class="p-4 font-semibold">Invoice</th><th class="p-4 font-semibold">Program</th><th class="p-4 font-semibold">Total</th><th class="p-4 font-semibold">Status</th><th class="p-4"><span class="sr-only">Aksi</span></th></tr>
        </thead>
        <tbody>
          <tr v-for="o in data?.data ?? []" :key="o.uuid" class="border-b border-kea-line last:border-0">
            <td class="p-4 font-mono text-sm">{{ o.invoice_number }}<br><span class="font-sans text-xs text-kea-muted">{{ formatDate(o.created_at) }}</span></td>
            <td class="p-4">{{ o.items[0]?.program_title }}<br><span class="text-sm text-kea-muted">{{ o.items[0]?.cohort_name }}</span></td>
            <td class="p-4 font-bold">{{ rupiah(o.total) }}</td>
            <td class="p-4"><span :class="o.status === 'paid' ? 'badge-olive' : o.status === 'pending' ? 'badge-ember' : 'badge-muted'">{{ label[o.status] }}</span></td>
            <td class="p-4 text-right"><NuxtLink v-if="o.status === 'pending'" :to="`/checkout/${o.uuid}`" class="font-bold">Bayar →</NuxtLink></td>
          </tr>
          <tr v-if="data && !data.data.length"><td colspan="5" class="p-6 text-center text-kea-muted">Belum ada pesanan.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
