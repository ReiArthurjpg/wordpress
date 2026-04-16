<script setup>
import { Mail, KeyRound, Loader2, LogIn, AlertCircle } from 'lucide-vue-next'

const email = ref('')
const senha = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

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
  <div class="min-h-screen bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-slate-100 via-white to-slate-50 flex items-center justify-center p-6 font-sans selection:bg-indigo-100 selection:text-indigo-900 text-slate-800">
    <div class="max-w-md w-full relative">
      <!-- Decoration Elements -->
      <div class="absolute -top-12 -left-12 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-12 -right-12 w-32 h-32 bg-cyan-500/10 rounded-full blur-3xl"></div>
      
      <!-- Card -->
      <div class="relative bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-2xl border border-slate-200/60 p-8 sm:p-12 overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-cyan-400"></div>
        
        <!-- Header -->
        <div class="text-center mb-10">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-50 border-2 border-white shadow-inner mb-6">
            <LogIn class="w-8 h-8 text-indigo-600" />
          </div>
          <h1 class="text-3xl font-black tracking-tight text-slate-900 mb-2">Bem-vindo(a)</h1>
          <p class="text-slate-500 text-sm font-medium">Faça login com sua conta gravada no painel administrativo.</p>
        </div>

        <!-- Error Msg -->
        <Transition name="list">
          <div v-if="errorMessage" class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-100 flex items-start gap-3">
            <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
            <p class="text-sm font-bold text-red-600">{{ errorMessage }}</p>
          </div>
        </Transition>

        <!-- Form -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <!-- Email -->
          <div class="space-y-2">
            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">E-mail</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <Mail class="w-5 h-5 text-slate-300 group-focus-within:text-indigo-500 transition-colors" />
              </div>
              <input 
                v-model="email"
                type="email" 
                required
                placeholder="seu@email.com"
                class="block w-full pl-12 pr-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner placeholder-slate-400 text-slate-700"
              />
            </div>
          </div>

          <!-- Password -->
          <div class="space-y-2">
            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">Senha</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <KeyRound class="w-5 h-5 text-slate-300 group-focus-within:text-indigo-500 transition-colors" />
              </div>
              <input 
                v-model="senha"
                type="password" 
                required
                placeholder="••••••••"
                class="block w-full pl-12 pr-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner placeholder-slate-400 text-slate-700"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="pt-6">
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-slate-900 hover:bg-indigo-600 text-white font-bold rounded-2xl transition-all duration-300 shadow-xl shadow-slate-900/10 hover:shadow-indigo-500/20 active:scale-[0.98] disabled:opacity-70 disabled:active:scale-100"
            >
              <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
              <span v-else>Entrar</span>
            </button>
          </div>
        </form>
      </div>
      
      <!-- Footer Link -->
      <p class="text-center mt-8 text-sm text-slate-500 font-medium">
        Não tem uma conta? <a href="#" class="text-indigo-600 font-bold hover:underline">Entre em contato</a>
      </p>
    </div>
  </div>
</template>
