<script setup>
import { ref, onMounted } from 'vue'
import { 
  User, Mail, Lock, LogIn, UserPlus, ArrowRight, ArrowLeft, 
  Globe, Zap, Camera, Calendar, CreditCard, Loader2, Eye, EyeOff
} from 'lucide-vue-next'

const isLogin = ref(true)
const fileInputRef = ref(null)
const isSubmitting = ref(false)
const showPassword = ref(false)
const showRegisterPassword = ref(false)
const showRegisterConfirmPassword = ref(false)

const loginData = ref({ email: '', password: '' })
const registerData = ref({ 
  name: '', 
  email: '', 
  cpf: '',
  birthDate: '',
  password: '', 
  confirmPassword: '',
  profileImage: null
})

onMounted(() => {
  const authCookie = useCookie('auth_user')
  if (authCookie.value) {
    navigateTo('/dashboard')
  }
})

const { $toast } = useNuxtApp()

const handleLoginSubmit = async () => {
  if (!loginData.value.email || !loginData.value.password) {
    $toast?.error('Preencha todos os campos.')
    return
  }
  
  isSubmitting.value = true
  
  try {
    const authHeader = 'Basic ' + btoa('Arthur:GOMhOPKqrNfzTPKuCPyFln67')
    const response = await $fetch('http://localhost:8000/wp-json/wp/v2/usuarios', {
      headers: {
        'Authorization': authHeader
      }
    })
    
    console.log('API Response:', response)
    const user = response.find(u => 
      u.acf?.email === loginData.value.email && 
      u.acf?.senha === loginData.value.password
    )
    
    if (user) {
      const authCookie = useCookie('auth_user')
      authCookie.value = user.id
      $toast?.success('Login realizado com sucesso!')
      navigateTo('/dashboard')
    } else {
      $toast?.error('Credenciais inválidas. Tente novamente.')
    }
  } catch (error) {
    console.error('Login error:', error)
    $toast?.error('Erro de conexão. Verifique o servidor.')
  } finally {
    isSubmitting.value = false
  }
}

const handleRegisterSubmit = async () => {
  if (registerData.value.password !== registerData.value.confirmPassword) {
    $toast?.error('As senhas não coincidem!')
    return
  }
  
  isSubmitting.value = true

  try {
    const authHeader = 'Basic ' + btoa('Arthur:GOMhOPKqrNfzTPKuCPyFln67')
    const formattedDate = registerData.value.birthDate 
      ? registerData.value.birthDate.replace(/-/g, '') 
      : ''

    const newUsuario = {
      title: registerData.value.name,
      status: 'publish',
      acf: {
        nome: registerData.value.name,
        email: registerData.value.email,
        senha: registerData.value.password,
        cpf: registerData.value.cpf,
        data_de_nascimento: formattedDate
      }
    }

    const response = await $fetch('http://localhost:8000/wp-json/wp/v2/usuarios', {
      method: 'POST',
      headers: {
        'Authorization': authHeader,
        'Content-Type': 'application/json'
      },
      body: newUsuario
    })

    if (response && response.id) {
      $toast?.success('Cadastro realizado com sucesso! Faça login.')
      isLogin.value = true
      registerData.value = { 
        name: '', email: '', cpf: '', birthDate: '', password: '', confirmPassword: '', profileImage: null
      }
    }
  } catch (error) {
    console.error('Register error:', error)
    $toast?.error('Erro ao cadastrar usuário.')
  } finally {
    isSubmitting.value = false
  }
}

const handleImageChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onloadend = () => {
      registerData.value.profileImage = reader.result
    }
    reader.readAsDataURL(file)
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#f8fafc] flex items-center justify-center p-4 font-sans transition-all duration-700">
    <div class="max-w-6xl w-full bg-white rounded-[32px] shadow-[0_20px_50px_rgba(0,0,0,0.1)] overflow-hidden flex flex-col md:flex-row min-h-[750px] border border-slate-100 relative">
      
      <!-- LADO: FORMULÁRIO -->
      <div 
        class="w-full md:w-1/2 p-8 lg:p-14 transition-all duration-700 ease-in-out flex flex-col justify-center z-20 bg-white"
        :class="isLogin ? 'md:translate-x-0' : 'md:translate-x-full'"
      >
        <div :key="isLogin ? 'login' : 'register'" class="animate-in fade-in slide-in-from-bottom-8 duration-700">

          <template v-if="isLogin">
            <div class="mb-10 text-center md:text-left">
              <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-200 mx-auto md:mx-0">
                <Zap class="text-white w-6 h-6" />
              </div>
              <h2 class="text-4xl font-extrabold text-slate-900 tracking-tight mb-3">Bem-vindo</h2>
              <p class="text-slate-500 text-lg">Acesse sua conta para gerenciar seus projetos.</p>
            </div>

            <form @submit.prevent="handleLoginSubmit" class="space-y-6">
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700 ml-1">E-mail</label>
                <div class="relative group">
                  <Mail class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5 group-focus-within:text-blue-600 transition-colors" />
                  <input
                    v-model="loginData.email"
                    type="email"
                    required
                    placeholder="nome@empresa.com"
                    class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800"
                  />
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700 ml-1">Senha</label>
                <div class="relative group">
                  <Lock class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5 group-focus-within:text-blue-600 transition-colors" />
                  <input
                    v-model="loginData.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    placeholder="••••••••"
                    class="w-full pl-12 pr-12 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800"
                  />
                  <button 
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute border-none bg-none right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors cursor-pointer"
                  >
                    <component :is="showPassword ? EyeOff : Eye" class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full bg-slate-900 hover:bg-black text-white font-bold py-4 rounded-2xl shadow-xl transition-all transform hover:-translate-y-1 active:scale-[0.98] flex items-center justify-center gap-3 disabled:opacity-70 disabled:active:scale-100"
              >
                <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
                <template v-else>
                  Entrar na plataforma
                  <LogIn class="w-5 h-5" />
                </template>
              </button>
            </form>
          </template>

          <template v-else>
            <div class="mb-6 text-center md:text-left">
              <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Criar conta</h2>
              <p class="text-slate-500">Preencha os dados abaixo para começar.</p>
            </div>

            <form @submit.prevent="handleRegisterSubmit" class="space-y-4">
              <!-- Upload de Foto de Perfil -->
              <div class="flex justify-center mb-6">
                <div class="relative">
                  <div 
                    @click="fileInputRef.click()"
                    class="w-24 h-24 rounded-full bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-all overflow-hidden group"
                  >
                    <img v-if="registerData.profileImage" :src="registerData.profileImage" alt="Preview" class="w-full h-full object-cover" />
                    <div v-else class="text-center">
                      <Camera class="w-6 h-6 text-slate-400 mx-auto group-hover:text-indigo-500 transition-colors" />
                      <span class="text-[10px] text-slate-400 font-medium">FOTO</span>
                    </div>
                  </div>
                  <input 
                    type="file" 
                    ref="fileInputRef" 
                    class="hidden" 
                    accept="image/*" 
                    @change="handleImageChange" 
                  />
                </div>
              </div>

              <div class="space-y-4">
                <div class="relative group">
                  <User class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                  <input
                    v-model="registerData.name"
                    type="text"
                    required
                    placeholder="Nome Completo"
                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all"
                  />
                </div>

                <div class="relative group">
                  <Mail class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                  <input
                    v-model="registerData.email"
                    type="email"
                    required
                    placeholder="Seu melhor e-mail"
                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all"
                  />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="relative group">
                    <CreditCard class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <input
                      v-model="registerData.cpf"
                      type="text"
                      required
                      placeholder="CPF"
                      class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all"
                    />
                  </div>
                  <div class="relative group">
                    <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <input
                      v-model="registerData.birthDate"
                      type="date"
                      required
                      class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all text-slate-500"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="relative group">
                    <Lock class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <input
                      v-model="registerData.password"
                      :type="showRegisterPassword ? 'text' : 'password'"
                      required
                      placeholder="Senha"
                      class="w-full pl-12 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none"
                    />
                    <button 
                      type="button"
                      @click="showRegisterPassword = !showRegisterPassword"
                      class="absolute border-none bg-none right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition-colors cursor-pointer"
                    >
                      <component :is="showRegisterPassword ? EyeOff : Eye" class="w-4 h-4" />
                    </button>
                  </div>
                  <div class="relative group">
                    <Lock class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <input
                      v-model="registerData.confirmPassword"
                      :type="showRegisterConfirmPassword ? 'text' : 'password'"
                      required
                      placeholder="Confirmar"
                      class="w-full pl-12 pr-10 py-3 bg-slate-50 border rounded-xl focus:ring-4 outline-none transition-all"
                      :class="registerData.confirmPassword && registerData.password !== registerData.confirmPassword 
                        ? 'border-red-300 focus:ring-red-100 focus:border-red-500' 
                        : 'border-slate-200 focus:ring-indigo-100 focus:border-indigo-500'"
                    />
                    <button 
                      type="button"
                      @click="showRegisterConfirmPassword = !showRegisterConfirmPassword"
                      class="absolute border-none bg-none right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition-colors cursor-pointer"
                    >
                      <component :is="showRegisterConfirmPassword ? EyeOff : Eye" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl shadow-xl shadow-indigo-200 transition-all transform hover:-translate-y-1 active:scale-[0.98] flex items-center justify-center gap-3 mt-4 disabled:opacity-70 disabled:active:scale-100"
              >
                <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
                <template v-else>
                  Finalizar Cadastro
                  <UserPlus class="w-5 h-5" />
                </template>
              </button>
            </form>
          </template>
        </div>
      </div>

      <!-- LADO: APOIO VISUAL -->
      <div 
        class="w-full md:w-1/2 absolute top-0 left-0 h-full overflow-hidden flex flex-col justify-center items-center p-12 text-center transition-all duration-700 ease-in-out z-10"
        :class="isLogin ? 'md:translate-x-full bg-[#0f172a]' : 'md:translate-x-0 bg-[#1e1b4b]'"
      >
        
        <div class="absolute top-0 left-0 w-full h-full">
          <div class="absolute top-[-10%] right-[-10%] w-[300px] h-[300px] bg-blue-600/20 rounded-full blur-[100px] animate-pulse"></div>
          <div class="absolute bottom-[-10%] left-[-10%] w-[300px] h-[300px] bg-purple-600/20 rounded-full blur-[100px]"></div>
          <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px"></div>
        </div>

        <div :key="isLogin ? 'msg-login' : 'msg-register'" class="relative z-10 animate-in fade-in zoom-in-95 duration-1000">
          <h2 class="text-5xl font-bold text-white mb-6 tracking-tight transition-all duration-500">
            {{ isLogin ? "Olá, amigo!" : "Tudo pronto?" }}
          </h2>
          <p class="text-slate-300 text-lg leading-relaxed mb-10 max-w-sm mx-auto font-light transition-all duration-500">
            {{ isLogin 
              ? "Insira seus dados pessoais e comece sua jornada conosco agora mesmo." 
              : "Se já possui uma conta, volte para o login e continue de onde parou." }}
          </p>

          <button
            @click="isLogin = !isLogin"
            type="button"
            class="group relative inline-flex items-center gap-3 px-12 py-4 bg-white/5 hover:bg-white/10 border border-white/20 rounded-full text-white font-semibold transition-all duration-300 hover:shadow-[0_0_20px_rgba(255,255,255,0.1)] active:scale-95 cursor-pointer"
          >
            <span class="relative z-10 flex items-center gap-2">
              {{ isLogin ? "Criar conta" : "Já tenho conta" }}
              <component :is="isLogin ? ArrowRight : ArrowLeft" class="w-5 h-5 transition-transform" :class="isLogin ? 'group-hover:translate-x-1' : 'group-hover:-translate-x-1'" />
            </span>
          </button>
          
          <div class="mt-16 flex justify-center gap-2">
              <div class="h-1.5 transition-all duration-500 rounded-full" :class="isLogin ? 'w-8 bg-blue-500' : 'w-4 bg-slate-700'"></div>
              <div class="h-1.5 transition-all duration-500 rounded-full" :class="!isLogin ? 'w-8 bg-indigo-500' : 'w-4 bg-slate-700'"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
