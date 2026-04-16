<script setup>
import { Mail, KeyRound, Loader2, AlertCircle, Eye, EyeOff, ShieldCheck, Sparkles, Lock } from 'lucide-vue-next'

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
  <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none absolute inset-0">
      <div class="absolute -left-32 -top-28 h-72 w-72 rounded-full bg-indigo-500/25 blur-3xl" />
      <div class="absolute -right-20 top-20 h-80 w-80 rounded-full bg-sky-500/20 blur-3xl" />
      <div class="absolute bottom-0 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-fuchsia-500/15 blur-3xl" />
    </div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-10 sm:px-6 lg:px-8">
      <div class="grid w-full gap-6 lg:grid-cols-2">
        <section class="hidden rounded-3xl border border-white/10 bg-white/5 p-10 backdrop-blur-xl lg:block">
          <p class="mb-3 inline-flex items-center gap-2 rounded-full border border-indigo-400/30 bg-indigo-500/20 px-3 py-1 text-xs font-semibold text-indigo-200">
            <Sparkles class="h-3.5 w-3.5" />
            Acesso inteligente
          </p>

          <h1 class="mb-4 text-4xl font-black leading-tight text-white">
            Bem-vindo de volta ao
            <span class="bg-gradient-to-r from-indigo-300 via-sky-300 to-fuchsia-300 bg-clip-text text-transparent">seu painel</span>
          </h1>

          <p class="mb-10 max-w-md text-sm leading-relaxed text-slate-300">
            Faça login para continuar gerenciando o sistema com segurança, rapidez e uma experiência totalmente renovada.
          </p>

          <div class="space-y-4">
            <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-900/50 p-4">
              <ShieldCheck class="h-5 w-5 text-emerald-300" />
              <span class="text-sm font-medium text-slate-200">Autenticação protegida</span>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-900/50 p-4">
              <Lock class="h-5 w-5 text-sky-300" />
              <span class="text-sm font-medium text-slate-200">Sessão rápida e confiável</span>
            </div>
          </div>
        </section>

        <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-950/50 backdrop-blur-xl sm:p-8">
          <div class="mb-8">
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.25em] text-indigo-300">Entrar</p>
            <h2 class="text-3xl font-black text-white">Acesse sua conta</h2>
            <p class="mt-2 text-sm text-slate-400">Use seu e-mail e senha para continuar.</p>
          </div>

          <Transition name="list">
            <div v-if="errorMessage" class="mb-6 flex items-start gap-3 rounded-xl border border-red-500/40 bg-red-500/10 p-4">
              <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-300" />
              <p class="text-sm font-semibold text-red-200">{{ errorMessage }}</p>
            </div>
          </Transition>

          <form @submit.prevent="handleLogin" class="space-y-5">
            <div class="space-y-2">
              <label class="pl-1 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">E-mail</label>
              <div class="group relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                  <Mail class="h-5 w-5 text-slate-500 transition-colors group-focus-within:text-indigo-300" />
                </div>
                <input
                  v-model="email"
                  type="email"
                  required
                  placeholder="Digite seu e-mail"
                  class="w-full rounded-xl border border-white/10 bg-slate-950/60 py-3.5 pl-12 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400/30"
                >
              </div>
            </div>

            <div class="space-y-2">
              <label class="block pl-1 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Senha</label>
              <div class="group relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                  <KeyRound class="h-5 w-5 text-slate-500 transition-colors group-focus-within:text-indigo-300" />
                </div>
                <input
                  v-model="senha"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="Digite sua senha"
                  class="w-full rounded-xl border border-white/10 bg-slate-950/60 py-3.5 pl-12 pr-12 text-sm text-white placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400/30"
                >
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-indigo-300"
                  :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                  @click="showPassword = !showPassword"
                >
                  <EyeOff v-if="showPassword" class="h-5 w-5" />
                  <Eye v-else class="h-5 w-5" />
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between gap-3 py-1">
              <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-400">
                <input type="checkbox" class="h-4 w-4 rounded border-white/20 bg-slate-950/70 text-indigo-500 focus:ring-indigo-500/40" />
                Lembrar de mim
              </label>
              <a href="#" class="text-xs font-semibold text-indigo-300 transition hover:text-indigo-200">Esqueceu sua senha?</a>
            </div>

            <button
              type="submit"
              :disabled="isSubmitting"
              class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 via-violet-500 to-sky-500 px-6 py-3.5 text-sm font-extrabold uppercase tracking-wider text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-70"
            >
              <Loader2 v-if="isSubmitting" class="h-5 w-5 animate-spin" />
              <template v-else>
                Entrar
                <span aria-hidden="true">→</span>
              </template>
            </button>
          </form>

          <div class="my-7 flex items-center gap-4">
            <div class="h-px flex-1 bg-white/10" />
            <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Ou continuar com</span>
            <div class="h-px flex-1 bg-white/10" />
          </div>

          <button class="group inline-flex w-full items-center justify-center gap-3 rounded-xl border border-white/10 bg-slate-950/60 px-6 py-3.5 text-sm font-semibold text-white transition hover:border-slate-400/40 hover:bg-slate-900">
            <svg class="h-5 w-5 transition group-hover:scale-110" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Google Account
          </button>

          <p class="mt-8 text-center text-xs text-slate-500">
            Não tem uma conta?
            <a href="#" class="font-semibold text-white transition hover:text-indigo-300">Criar conta</a>
          </p>
        </section>
      </div>
    </div>
  </div>
</template>
