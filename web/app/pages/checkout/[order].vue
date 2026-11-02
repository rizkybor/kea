<script setup lang="ts">
import type { Order, PaymentMethod } from '~/types/api'
import { rupiah, formatDate, formatDateTime } from '~/utils/format'

definePageMeta({ layout: 'bare', middleware: 'auth' })

const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const uuid = route.params.order as string

const { data, error, refresh } = await useAsyncData(`order-${uuid}`, () => api<{ data: Order }>(`/orders/${uuid}`))
const order = computed(() => data.value?.data ?? null)
const item = computed(() => order.value?.items[0] ?? null)

const step = computed<'form' | 'waiting' | 'paid' | 'closed'>(() => {
  const o = order.value
  if (!o) return 'closed'
  if (o.status === 'paid') return 'paid'
  if (o.status !== 'pending') return 'closed'
  return o.payment_instruction ? 'waiting' : 'form'
})

const methods: { id: PaymentMethod; name: string; hint: string }[] = [
  { id: 'va', name: 'Virtual Account', hint: 'BCA, BNI, BRI, Mandiri' },
  { id: 'qris', name: 'QRIS', hint: 'Semua e-wallet & m-banking' },
  { id: 'ewallet', name: 'E-Wallet', hint: 'GoPay, OVO, DANA, ShopeePay' },
  { id: 'cc', name: 'Kartu Kredit / Cicilan', hint: 'Cicilan 0% di bank mitra' },
]

const form = reactive({ method: 'va' as PaymentMethod, customer_name: '', customer_phone: '' })
watchEffect(() => {
  if (order.value && !form.customer_name) form.customer_name = order.value.customer_name ?? auth.user?.name ?? ''
  if (order.value && !form.customer_phone) form.customer_phone = order.value.customer_phone ?? auth.user?.phone ?? ''
})

const errors = ref<Record<string, string>>({})
const message = ref<string | null>(null)
const busy = ref(false)

async function run(fn: () => Promise<{ data: Order }>) {
  errors.value = {}
  message.value = null
  busy.value = true
  try {
    data.value = await fn()
  } catch (e) {
    const err = apiError(e)
    errors.value = Object.fromEntries(Object.entries(err.errors ?? {}).map(([k, v]) => [k, v[0] ?? '']))
    if (!Object.keys(errors.value).length) message.value = err.message
  } finally {
    busy.value = false
  }
}

const voucher = ref('')
const applyVoucher = () => run(() => api(`/orders/${uuid}/vouchers`, { method: 'POST', body: { code: voucher.value } }))
const removeVoucher = () => run(() => api(`/orders/${uuid}/vouchers`, { method: 'DELETE' }))
const pay = () => run(() => api(`/orders/${uuid}/pay`, { method: 'POST', body: form }))
const simulatePaid = () => run(() => api(`/orders/${uuid}/simulate-payment`, { method: 'POST' }))

// Status diperbarui otomatis: polling selama menunggu konfirmasi gateway.
const now = ref(Date.now())
let poll: ReturnType<typeof setInterval> | undefined
let tick: ReturnType<typeof setInterval> | undefined
onMounted(() => {
  tick = setInterval(() => { now.value = Date.now() }, 1000)
  poll = setInterval(() => { if (step.value === 'waiting') refresh() }, 5000)
})
onBeforeUnmount(() => { clearInterval(poll); clearInterval(tick) })

const remaining = computed(() => {
  const until = order.value?.payment_instruction?.expires_at ?? order.value?.reserved_until
  if (!until) return null
  const ms = new Date(until).getTime() - now.value
  if (ms <= 0) return '00:00'
  const m = Math.floor(ms / 60000)
  const s = Math.floor((ms % 60000) / 1000)
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

const copied = ref(false)
async function copyVa(value: string) {
  await navigator.clipboard?.writeText(value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

const stepIndex = computed(() => ({ form: 0, waiting: 1, paid: 2, closed: 0 })[step.value])

useSeoMeta({ title: 'Checkout', robots: 'noindex' })
</script>

<template>
  <div>
    <header class="border-b border-kea-line">
      <div class="container-kea flex flex-wrap items-center justify-between gap-4 py-4">
        <KeaLogo />
        <ol aria-label="Tahap checkout" class="flex flex-wrap gap-x-6 gap-y-1 text-sm font-semibold">
          <li v-for="(label, i) in ['Data & pembayaran', 'Selesaikan bayar', 'Mulai belajar']" :key="label" :aria-current="i === stepIndex ? 'step' : undefined" :class="i === stepIndex ? 'text-kea-forest' : 'text-[#6B706A]'">
            <span :class="i === stepIndex ? 'border-b-[3px] border-kea-ember pb-1' : ''">{{ i + 1 }} · {{ label }}</span>
          </li>
        </ol>
      </div>
    </header>

    <EmptyState v-if="error || !order" class="container-kea mt-10" title="Order tidak ditemukan" body="Order ini tidak ada atau bukan milik akunmu.">
      <NuxtLink to="/program" class="btn-primary">Lihat program</NuxtLink>
    </EmptyState>

    <main v-else class="container-kea flex flex-col gap-8 py-10 lg:flex-row lg:items-start">
      <section class="flex min-w-0 flex-1 flex-col gap-6">
        <ErrorAlert :message="message" />

        <template v-if="step === 'form'">
          <h1 class="text-[32px] font-extrabold">Checkout</h1>
          <div class="card flex flex-col gap-4 p-6">
            <h2 class="text-lg font-bold">Data peserta</h2>
            <div class="grid gap-4 sm:grid-cols-2">
              <label class="field">Nama lengkap
                <input v-model="form.customer_name" autocomplete="name" class="input" :class="errors.customer_name && 'input-error'">
                <span v-if="errors.customer_name" class="error-text">{{ errors.customer_name }}</span>
              </label>
              <label class="field">No. WhatsApp
                <input v-model="form.customer_phone" type="tel" autocomplete="tel" placeholder="08xx-xxxx-xxxx" class="input" :class="errors.customer_phone && 'input-error'">
                <span v-if="errors.customer_phone" class="error-text">{{ errors.customer_phone }}</span>
              </label>
            </div>
          </div>
          <fieldset v-if="order.total > 0" class="card flex flex-col gap-3 p-6">
            <legend class="sr-only">Metode pembayaran</legend>
            <h2 class="text-lg font-bold" aria-hidden="true">Metode pembayaran</h2>
            <button
              v-for="m in methods"
              :key="m.id"
              type="button"
              :aria-pressed="form.method === m.id"
              class="flex min-h-[60px] w-full cursor-pointer items-center justify-between gap-3 rounded-xl px-4 py-3 text-left"
              :class="form.method === m.id ? 'border-2 border-kea-forest bg-kea-olive-50' : 'border border-kea-field bg-white hover:border-kea-forest'"
              @click="form.method = m.id"
            >
              <span class="flex flex-col"><b>{{ m.name }}</b><span class="text-[13px] text-kea-muted">{{ m.hint }}</span></span>
              <span class="flex size-5 items-center justify-center rounded-full border-2" :class="form.method === m.id ? 'border-kea-forest' : 'border-kea-field'" aria-hidden="true">
                <span v-if="form.method === m.id" class="size-2.5 rounded-full bg-kea-forest" />
              </span>
            </button>
            <span v-if="errors.method" class="error-text">{{ errors.method }}</span>
          </fieldset>
        </template>

        <div v-else-if="step === 'waiting' && order.payment_instruction" class="card flex flex-col gap-5 p-6 md:p-8">
          <h1 class="text-[28px] font-extrabold">Selesaikan pembayaran</h1>
          <p class="text-[15px] text-kea-muted">
            Bayar sebelum <b class="text-kea-ink">{{ formatDateTime(order.payment_instruction.expires_at) }}</b>
            <span v-if="remaining" class="badge-ember ml-1" aria-live="off">{{ remaining }}</span>.
            Kuota kursi kamu ditahan selama 30 menit.
          </p>
          <div class="flex flex-col items-start gap-3 rounded-xl bg-kea-surface p-5">
            <span class="text-sm font-bold text-kea-muted">{{ order.payment_instruction.channel }}</span>
            <template v-if="order.payment_instruction.va_number">
              <span class="font-mono text-[28px] font-extrabold tracking-wider">{{ order.payment_instruction.va_number }}</span>
              <button type="button" class="btn-outline min-h-9 py-1.5 text-sm" @click="copyVa(order.payment_instruction.va_number!)">{{ copied ? 'Tersalin ✓' : 'Salin nomor VA' }}</button>
            </template>
            <QrCode v-else-if="order.payment_instruction.qr_string" :value="order.payment_instruction.qr_string" :size="200" label="QR pembayaran" />
            <a v-if="order.payment_instruction.redirect_url" :href="order.payment_instruction.redirect_url" target="_blank" rel="noopener" class="btn-secondary">Lanjut ke halaman pembayaran</a>
            <span class="text-[15px]">Total: <b>{{ rupiah(order.total) }}</b></span>
          </div>
          <p class="text-sm text-kea-muted" role="status">Status diperbarui otomatis setelah pembayaran terkonfirmasi gateway.</p>
          <button v-if="order.simulator" type="button" class="btn-outline self-start" :disabled="busy" @click="simulatePaid">Simulasikan pembayaran berhasil</button>
        </div>

        <div v-else-if="step === 'paid'" class="card flex flex-col items-start gap-4 p-6 md:p-8">
          <span class="flex size-16 items-center justify-center rounded-full bg-kea-olive-50">
            <svg aria-hidden="true" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#0E3B34" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10" /></svg>
          </span>
          <h1 class="text-[28px] font-extrabold">Pembayaran berhasil</h1>
          <p class="text-[15px] text-kea-muted">
            Kamu terdaftar di <b class="text-kea-ink">{{ item?.program_title }}</b><template v-if="item?.cohort_name"> · {{ item.cohort_name }}</template>.
            Invoice {{ order.invoice_number }} & jadwal dikirim ke email dan WhatsApp.
          </p>
          <NuxtLink to="/dashboard" class="btn-primary">Masuk ke Dashboard</NuxtLink>
        </div>

        <div v-else class="card flex flex-col items-start gap-4 p-6 md:p-8">
          <h1 class="text-[28px] font-extrabold">{{ order.status === 'expired' ? 'Waktu pembayaran habis' : 'Pembayaran gagal' }}</h1>
          <p class="text-[15px] text-kea-muted">Kursi yang direservasi sudah dilepas. Silakan buat pesanan baru bila masih ingin mendaftar.</p>
          <NuxtLink v-if="item?.program_slug" :to="`/program/${item.program_slug}`" class="btn-primary">Daftar ulang</NuxtLink>
        </div>
      </section>

      <aside aria-label="Ringkasan pesanan" class="card flex flex-col gap-5 p-6 lg:w-[400px] lg:flex-none">
        <h2 class="text-lg font-bold">Ringkasan pesanan</h2>
        <div v-if="item" class="flex gap-3">
          <ProgramThumb v-if="item.program_type" :src="item.thumbnail_url" :title="''" :type="item.program_type" height="h-14 w-20 flex-none rounded-kea-md" />
          <span class="flex flex-col"><b>{{ item.program_title }}</b><span class="text-sm text-kea-muted">{{ [item.program_type_label, item.cohort_name].filter(Boolean).join(' · ') }}</span>
            <span v-if="item.cohort_starts_at" class="text-sm text-kea-muted">Mulai {{ formatDate(item.cohort_starts_at) }}</span></span>
        </div>

        <form v-if="step === 'form' && order.subtotal > 0 && !order.voucher_code" class="flex items-end gap-2" @submit.prevent="applyVoucher">
          <label class="field flex-1">Kode voucher
            <input v-model="voucher" placeholder="KEAHEMAT" class="input uppercase" :class="errors.code && 'input-error'" autocomplete="off">
          </label>
          <button type="submit" class="btn-outline" :disabled="busy || !voucher">Pakai</button>
        </form>
        <span v-if="errors.code" class="error-text -mt-3">{{ errors.code }}</span>
        <div v-if="order.voucher_code" class="flex items-center justify-between rounded-kea-md bg-kea-olive-50 px-3 py-2 text-sm font-semibold text-kea-olive-900">
          <span>Voucher {{ order.voucher_code }} diterapkan</span>
          <button v-if="step === 'form'" type="button" class="text-kea-olive-900 underline" @click="removeVoucher">Hapus</button>
        </div>

        <dl class="grid grid-cols-[1fr_auto] gap-y-2 border-t border-kea-line pt-4 text-[15px]">
          <dt class="text-kea-muted">Harga program</dt><dd class="text-right">{{ rupiah(order.subtotal) }}</dd>
          <dt class="text-kea-muted">Diskon</dt><dd class="text-right">{{ order.discount ? `− ${rupiah(order.discount)}` : 'Rp0' }}</dd>
          <dt class="border-t border-kea-line pt-2 font-bold">Total</dt><dd class="border-t border-kea-line pt-2 text-right text-xl font-extrabold">{{ rupiah(order.total) }}</dd>
        </dl>

        <template v-if="step === 'form'">
          <button type="button" class="btn-primary w-full py-4 text-base" :disabled="busy" @click="pay">{{ busy ? 'Memproses…' : order.total > 0 ? 'Bayar sekarang' : 'Daftar sekarang' }}</button>
          <span class="text-xs text-kea-muted">Dengan membayar, kamu setuju dengan Syarat & Ketentuan dan kebijakan refund. Reservasi berakhir dalam {{ remaining }}.</span>
        </template>
      </aside>
    </main>
  </div>
</template>
