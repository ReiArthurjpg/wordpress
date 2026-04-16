<script setup>
import { Mail, KeyRound, Loader2, AlertCircle, Eye, EyeOff } from 'lucide-vue-next'

const email = ref('')
const senha = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)

onMounted(() => {
  const authCookie = useCookie('auth_user')
  if (authCookie.value) {
    navigateTo('/dashboard')
  }
})

const handleLogin = async () => {
  if (!email.value || !senha.value) {
    errorMessage.value = 'Preencha todos os campos.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch('http://localhost:8000/wp-json/wp/v2/usuarios')

    // Find matching user
    const user = response.find(u =>
      u.acf?.email === email.value &&
      u.acf?.senha === senha.value
    )

    if (user) {
      // Login Success
      const authCookie = useCookie('auth_user')
      authCookie.value = user.id
      navigateTo('/dashboard')
    } else {
      errorMessage.value = 'Credenciais inválidas. Tente novamente.'
    }
  } catch (error) {
    console.error('Login error:', error)
    errorMessage.value = 'Ocorreu um erro no servidor. Tente mais tarde.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#f3f2ef] px-4 py-10 text-[#191919] sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-6xl">
      <div class="overflow-hidden rounded-3xl border border-[#d9d9d9] bg-white shadow-[0_20px_60px_rgba(15,23,42,0.1)]">
        <div class="grid lg:grid-cols-2">
          <section class="relative hidden min-h-[660px] overflow-hidden bg-gradient-to-br from-[#0a66c2] via-[#3f8ee0] to-[#8bb7ea] p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div>
              <p class="inline-flex items-center rounded-full border border-white/35 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">
                Welcome
              </p>
              <h1 class="mt-6 max-w-sm text-4xl font-bold leading-tight">
                Entre e continue seu trabalho com clareza.
              </h1>
              <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/85">
                Acesse sua conta para gerenciar o sistema, acompanhar seu painel e manter tudo organizado.
              </p>
            </div>

            <div class="space-y-3">
              <div class="rounded-2xl border border-white/30 bg-white/15 p-4 backdrop-blur-sm">
                <p class="text-xs uppercase tracking-[0.2em] text-white/80">Passo 1</p>
                <p class="mt-1 text-lg font-semibold">Faça login</p>
              </div>
              <div class="rounded-2xl border border-white/25 bg-black/15 p-4 backdrop-blur-sm">
                <p class="text-xs uppercase tracking-[0.2em] text-white/75">Passo 2</p>
                <p class="mt-1 text-lg font-semibold">Acesse o dashboard</p>
              </div>
            </div>
          </section>

          <section class="min-h-[660px] p-6 sm:p-10 lg:p-12">
            <div class="mx-auto flex h-full w-full max-w-md flex-col justify-center">
              <div class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0a66c2]">Login</p>
                <h2 class="mt-2 text-4xl font-bold text-[#191919]">Entrar</h2>
                <p class="mt-2 text-sm text-[#5e5e5e]">Use seu e-mail e senha para continuar.</p>
              </div>

              <Transition name="list">
                <div v-if="errorMessage" class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
                  <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
                  <p class="text-sm font-semibold text-red-600">{{ errorMessage }}</p>
                </div>
              </Transition>

              <form @submit.prevent="handleLogin" class="space-y-5">
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-[#2b2b2b]">E-mail</label>
                  <div class="group relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                      <Mail class="h-4.5 w-4.5 text-[#6b7280] transition-colors group-focus-within:text-[#0a66c2]" />
                    </div>
                    <input
                      v-model="email"
                      type="email"
                      required
                      placeholder="Digite seu e-mail"
                      class="w-full rounded-xl border border-[#d1d5db] bg-white py-3.5 pl-11 pr-4 text-sm text-[#191919] placeholder:text-[#9ca3af] transition focus:border-[#0a66c2] focus:outline-none focus:ring-2 focus:ring-[#0a66c2]/20"
                    >
                  </div>
                </div>

                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <label class="text-sm font-semibold text-[#2b2b2b]">Senha</label>
                    <a href="#" class="text-xs font-semibold text-[#0a66c2] hover:underline">Esqueceu a senha?</a>
                  </div>
                  <div class="group relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                      <KeyRound class="h-4.5 w-4.5 text-[#6b7280] transition-colors group-focus-within:text-[#0a66c2]" />
                    </div>
                    <input
                      v-model="senha"
                      :type="showPassword ? 'text' : 'password'"
                      required
                      placeholder="Digite sua senha"
                      class="w-full rounded-xl border border-[#d1d5db] bg-white py-3.5 pl-11 pr-12 text-sm text-[#191919] placeholder:text-[#9ca3af] transition focus:border-[#0a66c2] focus:outline-none focus:ring-2 focus:ring-[#0a66c2]/20"
                    >
                    <button
                      type="button"
                      class="absolute inset-y-0 right-0 flex items-center pr-4 text-[#6b7280] transition hover:text-[#0a66c2]"
                      :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                      @click="showPassword = !showPassword"
                    >
                      <EyeOff v-if="showPassword" class="h-5 w-5" />
                      <Eye v-else class="h-5 w-5" />
                    </button>
                  </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-sm text-[#5e5e5e]">
                  <input type="checkbox" class="h-4 w-4 rounded border-[#c4c7cc] text-[#0a66c2] focus:ring-[#0a66c2]/30" />
                  Lembrar de mim
                </label>

                <button
                  type="submit"
                  :disabled="isSubmitting"
                  class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#0a2a73] px-6 py-3.5 text-base font-bold text-white shadow-[0_8px_24px_rgba(10,42,115,0.25)] transition hover:bg-[#081f56] disabled:cursor-not-allowed disabled:opacity-70"
                >
                  <Loader2 v-if="isSubmitting" class="h-5 w-5 animate-spin" />
                  <template v-else>
                    Login
                  </template>
                </button>
              </form>

            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>
