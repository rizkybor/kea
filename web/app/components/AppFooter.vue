<template>
  <footer class="bg-kea-forest text-white">
    <div class="container-kea flex flex-wrap justify-between gap-10 py-14">
      <div class="flex flex-[1_1_320px] flex-col gap-3">
        <KeaLogo light :size="40" />
        <span class="text-[15px] text-kea-on-forest">Knowledge Ecosystem for Advancement</span>
        <span class="text-sm text-kea-on-forest">by Jendela Cakra Digital</span>
        <span class="text-sm italic text-kea-on-forest">Transforming Knowledge into Sustainable Competence.</span>
      </div>
      <nav aria-label="Program" class="flex flex-col gap-2.5 text-[15px]">
        <b>Program</b>
        <NuxtLink to="/program?type=bootcamp" class="text-white hover:text-kea-on-forest">Bootcamp</NuxtLink>
        <NuxtLink to="/program?type=video_course" class="text-white hover:text-kea-on-forest">Video Course</NuxtLink>
        <NuxtLink to="/program?type=short_class" class="text-white hover:text-kea-on-forest">Short Class</NuxtLink>
      </nav>
      <nav aria-label="Perusahaan" class="flex flex-col gap-2.5 text-[15px]">
        <b>Perusahaan</b>
        <NuxtLink to="/#karier" class="text-white hover:text-kea-on-forest">Hiring Partner</NuxtLink>
        <NuxtLink to="/#faq" class="text-white hover:text-kea-on-forest">FAQ</NuxtLink>
        <NuxtLink to="/verify" class="text-white hover:text-kea-on-forest">Verifikasi Sertifikat</NuxtLink>
      </nav>
    </div>
    <div class="border-t border-white/15">
      <p class="container-kea py-5 text-[13px] text-kea-on-forest">© {{ new Date().getFullYear() }} Jendela Cakra Digital. Seluruh hak cipta dilindungi.</p>
    </div>
  </footer>
</template>
