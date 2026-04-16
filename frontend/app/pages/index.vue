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
  <div class="min-h-screen flex items-center justify-center bg-slate-950 font-sans text-slate-300 p-6">
    <div class="w-full max-w-md">
      
      <!-- Cabeçalho -->
      <div class="mb-8">
        <h1 class="text-[2.5rem] font-black italic tracking-tighter text-white mb-2 uppercase">
          Acesse sua <span class="text-indigo-500">Conta</span>
        </h1>
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">
          Informe suas credenciais para gerenciar o sistema
        </p>

        <!-- Status Dots -->
        <div class="flex items-center gap-4 text-[9px] font-bold text-slate-400 uppercase tracking-wider">
          <div class="flex items-center gap-1.5">
            <div class="w-1.5 h-1.5 rounded-full bg-cyan-400"></div>
            Seguro
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
            Rápido
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
            Confiável
          </div>
        </div>
      </div>

      <!-- Erros -->
      <Transition name="list">
        <div v-if="errorMessage" class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-start gap-3">
          <AlertCircle class="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
          <p class="text-sm font-bold text-red-400">{{ errorMessage }}</p>
        </div>
      </Transition>

      <form @submit.prevent="handleLogin" class="space-y-5">
        <!-- E-Mail -->
        <div class="space-y-2">
          <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest pl-1">E-mail</label>
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <Mail class="w-5 h-5 text-slate-600 group-focus-within:text-indigo-500 transition-colors" />
            </div>
            <input 
              v-model="email"
              type="email" 
              required
              placeholder="Digite seu e-mail"
              class="block w-full pl-12 pr-4 py-3.5 bg-[#0f1115] border border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm font-medium text-white placeholder-slate-600"
            />
          </div>
        </div>

        <!-- Senha -->
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest pl-1 block mb-2">Senha</label>
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <KeyRound class="w-5 h-5 text-slate-600 group-focus-within:text-indigo-500 transition-colors" />
            </div>
            <input 
              v-model="senha"
              type="password" 
              required
              placeholder="Digite sua senha"
              class="block w-full pl-12 pr-12 py-3.5 bg-[#0f1115] border border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm font-medium text-white placeholder-slate-600"
            />
            <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-600 hover:text-slate-400 transition-colors">
              <Eye class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Lembrar e Esqueci -->
        <div class="flex items-center justify-between pt-1 pb-2">
          <label class="flex items-center gap-2 cursor-pointer group">
            <div class="relative flex items-center justify-center w-4 h-4 bg-[#0f1115] border border-slate-700 rounded transition-colors group-hover:border-indigo-500"></div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Lembrar de mim</span>
          </label>
          <a href="#" class="text-[10px] font-bold italic text-indigo-500 hover:text-indigo-400 transition-colors tracking-wide">
            Esqueceu sua senha?
          </a>
        </div>

        <!-- Botão Entrar -->
        <button 
          type="submit"
          :disabled="isSubmitting"
          class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black italic uppercase tracking-wider rounded-xl transition-all disabled:opacity-70 disabled:active:scale-100"
        >
          <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
          <template v-else>
            Entrar
            <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
          </template>
        </button>
      </form>

      <!-- Divisor -->
      <div class="flex items-center my-8">
        <div class="flex-1 border-t border-slate-800"></div>
        <span class="px-4 text-[10px] font-bold text-slate-600 uppercase tracking-widest">Ou continuar com</span>
        <div class="flex-1 border-t border-slate-800"></div>
      </div>

      <!-- Botão Google -->
      <button class="w-full flex items-center justify-center gap-3 px-6 py-3.5 bg-[#0a0c0f] hover:bg-[#111419] border border-slate-800 rounded-xl transition-all text-sm font-bold text-white uppercase tracking-wider group">
        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Google Account
      </button>

      <!-- Footer -->
      <p class="text-center mt-10 text-[11px] font-bold text-slate-500 uppercase tracking-widest">
        Não tem uma conta? <a href="#" class="text-white hover:text-indigo-400 transition-colors">Criar conta</a>
      </p>

    </div>
  </div>
</template>
