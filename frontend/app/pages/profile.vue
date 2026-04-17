<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
  User, Mail, Lock, ArrowLeft, 
  Camera, Calendar, CreditCard, 
  Zap, Save, Check,
  MapPin, Smartphone, ChevronDown, LogOut, Settings, X, Eye, EyeOff, ShieldCheck
} from 'lucide-vue-next'

const appLoading = useState('appLoading')
const { $toast } = useNuxtApp()
const authHeader = 'Basic ' + btoa('Arthur:GOMhOPKqrNfzTPKuCPyFln67')

// Auth check
const authCookie = useCookie('auth_user')
if (!authCookie.value) {
  navigateTo('/')
}

// Data fetching
const { data: user, refresh, pending } = await useFetch(`http://localhost:8000/wp-json/wp/v2/usuarios/${authCookie.value}`, {
  headers: { 'Authorization': authHeader }
})

// States
const showProfileMenu = ref(false)
const isSaving = ref(false)
const showSaveSuccess = ref(false)

// Password Modal
const isPasswordModalOpen = ref(false)
const showPasswords = ref(false)
const passwordData = ref({
  current: '',
  new: '',
  confirm: ''
})
const passwordStatus = ref(null)

// Image handling
const fileInput = ref(null)
const selectedFile = ref(null)
const imagePreview = ref(null)

const triggerFileInput = () => {
  fileInput.value?.click()
}

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  selectedFile.value = file
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const handleLogout = () => {
  authCookie.value = null
  $toast?.success('Você saiu do sistema.')
  navigateTo('/')
}

const handleSave = async () => {
  if (isSaving.value) return
  isSaving.value = true
  appLoading.value = true

  try {
    let imageId = user.value.acf?.imagem?.id || null

    if (selectedFile.value) {
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      formData.append('title', `Avatar ${user.value.acf.nome}`)
      
      const mediaResponse = await $fetch('http://localhost:8000/wp-json/wp/v2/media', {
        method: 'POST',
        headers: { 'Authorization': authHeader },
        body: formData
      })
      imageId = mediaResponse.id
    }

    const formattedDate = user.value.acf.data_de_nascimento?.includes('-') 
      ? user.value.acf.data_de_nascimento.replace(/-/g, '') 
      : user.value.acf.data_de_nascimento

    await $fetch(`http://localhost:8000/wp-json/wp/v2/usuarios/${user.value.id}`, {
      method: 'POST',
      headers: { 'Authorization': authHeader },
      body: {
        title: user.value.acf.nome,
        acf: {
          nome: user.value.acf.nome,
          email: user.value.acf.email,
          cpf: user.value.acf.cpf,
          data_de_nascimento: formattedDate,
          phone: user.value.acf.phone || '',
          location: user.value.acf.location || '',
          imagem: imageId
        }
      }
    })

    showSaveSuccess.value = true
    setTimeout(() => showSaveSuccess.value = false, 3000)
    selectedFile.value = null
    imagePreview.value = null
    await refresh()
    $toast?.success('Perfil atualizado com sucesso!')
  } catch (error) {
    console.error('Erro ao salvar perfil:', error)
    $toast?.error('Erro ao salvar alterações.')
  } finally {
    isSaving.value = false
    appLoading.value = false
  }
}

const handlePasswordChange = async () => {
  if (passwordData.value.new !== passwordData.value.confirm) {
    $toast?.error('As senhas não coincidem.')
    return
  }
  
  isSaving.value = true
  appLoading.value = true

  try {
    await $fetch(`http://localhost:8000/wp-json/wp/v2/usuarios/${user.value.id}`, {
      method: 'POST',
      headers: { 'Authorization': authHeader },
      body: {
        acf: {
          senha: passwordData.value.new
        }
      }
    })

    passwordStatus.value = 'success'
    setTimeout(() => {
      isPasswordModalOpen.value = false
      passwordStatus.value = null
      passwordData.value = { current: '', new: '', confirm: '' }
    }, 2000)
  } catch (error) {
    console.error('Erro ao operar senha:', error)
    $toast?.error('Erro ao atualizar senha.')
  } finally {
    isSaving.value = false
    appLoading.value = false
  }
}
</script>

<template>
  <div v-if="user" class="min-h-screen bg-[#f8fafc] font-sans text-slate-900 pb-20">
    <!-- Input de arquivo invisível -->
    <input 
      type="file" 
      ref="fileInput" 
      class="hidden" 
      accept="image/*" 
      @change="onFileChange"
    />

    <!-- HEADER -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div @click="navigateTo('/dashboard')" class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200 cursor-pointer hover:scale-105 transition-transform">
            <Zap class="size-6 text-white fill-white" />
          </div>
          <p class="font-black text-xl tracking-tighter text-slate-800">DASH<span class="text-blue-600">PRO</span></p>
        </div>

        <div class="flex items-center gap-4">
          <div class="relative">
            <button 
              @click="showProfileMenu = !showProfileMenu"
              class="flex items-center gap-3 p-1.5 pr-3 hover:bg-slate-50 rounded-2xl transition-all border border-transparent hover:border-slate-100"
            >
              <div v-if="user.acf?.imagem" class="w-9 h-9 rounded-xl overflow-hidden shadow-sm">
                <img :src="user.acf.imagem.url" class="w-full h-full object-cover" />
              </div>
              <div v-else class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                {{ user.acf?.nome?.charAt(0).toUpperCase() || 'U' }}
              </div>
              <div class="hidden md:block text-left">
                <p class="text-sm font-bold leading-none">{{ user.acf?.nome }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ user.acf?.email }}</p>
              </div>
              <ChevronDown :class="['size-4 text-slate-400 transition-transform', showProfileMenu ? 'rotate-180' : '']" />
            </button>

            <div v-if="showProfileMenu" class="absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-50 animate-in fade-in zoom-in-95 duration-200">
              <div class="px-4 py-3 border-b border-slate-50 mb-1">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sua Conta</p>
              </div>
              <button @click="showProfileMenu = false" class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-3 hover:bg-slate-50 text-slate-700 transition-colors">
                <User class="size-4" /> Perfil
              </button>
              <button class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-3 hover:bg-slate-50 text-slate-700 transition-colors">
                <Settings class="size-4" /> Configurações
              </button>
              <div class="h-px bg-slate-50 my-1"></div>
              <button @click="handleLogout" class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-3 hover:bg-red-50 text-red-600 transition-colors">
                <LogOut class="size-4" /> Sair da conta
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <!-- TÍTULO DA PÁGINA -->
      <section class="mb-10 animate-in fade-in slide-in-from-left-6 duration-700">
        <div class="flex items-center gap-4 mb-2">
           <button @click="navigateTo('/dashboard')" class="p-2 hover:bg-white rounded-xl transition-all text-slate-400 hover:text-slate-900 border border-transparent hover:border-slate-100">
              <ArrowLeft class="size-5" />
           </button>
           <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Meu Perfil</h1>
        </div>
        <p class="text-slate-500 text-lg ml-11 leading-relaxed">
          Personalize sua experiência e mantenha os seus dados atualizados.
        </p>
      </section>

      <!-- CONTEÚDO PRINCIPAL -->
      <div class="w-full animate-in fade-in slide-in-from-bottom-10 duration-1000 delay-200">
        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-[0_20px_50px_rgba(0,0,0,0.02)] overflow-hidden">
          
          <!-- HEADER DO CARD (CAPA & AVATAR) -->
          <div class="h-40 bg-gradient-to-r from-blue-600 to-indigo-700 relative">
            <div class="absolute -bottom-14 left-10">
              <div class="relative group">
                <div class="w-32 h-32 rounded-[2.5rem] bg-white p-1.5 shadow-xl">
                  <div class="w-full h-full rounded-[2.2rem] bg-indigo-50 flex items-center justify-center overflow-hidden border-2 border-indigo-100">
                    <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                    <img v-else-if="user.acf?.imagem" :src="user.acf.imagem.url" class="w-full h-full object-cover" />
                    <span v-else class="text-4xl font-black text-indigo-600">
                      {{ user.acf?.nome?.charAt(0).toUpperCase() || 'U' }}
                    </span>
                  </div>
                </div>
                <button 
                  @click="triggerFileInput"
                  class="absolute bottom-1 right-1 p-3 bg-white rounded-2xl shadow-lg border border-slate-100 text-slate-600 hover:text-blue-600 transition-all hover:scale-110 active:scale-90"
                >
                  <Camera class="size-5" />
                </button>
              </div>
            </div>
          </div>

          <div class="pt-20 pb-12 px-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12 pb-8 border-b border-slate-50">
              <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ user.acf?.nome }}</h2>
              </div>
              <div class="flex items-center gap-3">
                <div v-if="showSaveSuccess" class="flex items-center gap-2 px-5 py-2.5 bg-green-50 text-green-600 rounded-2xl font-bold text-sm animate-in fade-in slide-in-from-right-4">
                  <Check class="size-4" /> Dados salvos!
                </div>
                <button 
                  @click="handleSave"
                  :disabled="isSaving"
                  class="flex items-center gap-2 px-10 py-4 bg-[#22c55e] text-white rounded-2xl font-black text-sm hover:bg-[#16a34a] transition-all shadow-lg shadow-green-100 active:scale-95 disabled:opacity-70"
                >
                  <div v-if="isSaving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <template v-else>
                    <Save class="size-5" />
                    Salvar Alterações
                  </template>
                </button>
              </div>
            </div>

            <!-- FORMULÁRIO DE DADOS -->
            <form class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8" @submit.prevent="handleSave">
              
              <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Nome Completo</label>
                <div class="relative group">
                  <User class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input 
                    type="text" 
                    v-model="user.acf.nome"
                    class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" 
                  />
                </div>
              </div>

              <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">E-mail Corporativo</label>
                <div class="relative group">
                  <Mail class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input 
                    type="email" 
                    v-model="user.acf.email"
                    class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" 
                  />
                </div>
              </div>

              <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Documento (CPF)</label>
                <div class="relative group">
                  <CreditCard class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input 
                    type="text" 
                    v-model="user.acf.cpf"
                    class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" 
                  />
                </div>
              </div>

              <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Telemóvel / Celular</label>
                <div class="relative group">
                  <Smartphone class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input 
                    type="text" 
                    v-model="user.acf.phone"
                    placeholder="Ex: (11) 98765-4321"
                    class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" 
                  />
                </div>
              </div>

              <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Data de Nascimento</label>
                <div class="relative group">
                  <Calendar class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input 
                    type="date" 
                    v-model="user.acf.data_de_nascimento"
                    class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" 
                  />
                </div>
              </div>

              <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Localização Atual</label>
                <div class="relative group">
                  <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input 
                    type="text" 
                    v-model="user.acf.location"
                    placeholder="Ex: São Paulo, Brasil"
                    class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" 
                  />
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- SEÇÃO DE SEGURANÇA -->
        <div class="mt-10 animate-in fade-in slide-in-from-bottom-6 duration-1000 delay-400">
          <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-6">
              <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                <Lock class="size-8" />
              </div>
              <div>
                <h3 class="text-xl font-black text-slate-900">Segurança da Conta</h3>
                <p class="text-slate-500 mt-1 font-medium">Mantenha sua conta protegida atualizando sua senha regularmente.</p>
              </div>
            </div>
            <button 
              @click="isPasswordModalOpen = true"
              class="w-full md:w-auto px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-sm hover:bg-slate-800 transition-all active:scale-95 flex items-center justify-center gap-2"
            >
              <Settings class="size-5" />
              Alterar Senha
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- MODAL DE ALTERAÇÃO DE SENHA -->
    <div v-if="isPasswordModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center px-4">
      <!-- Backdrop -->
      <div 
        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-300"
        @click="!isSaving && (isPasswordModalOpen = false)"
      ></div>
      
      <!-- Conteúdo do Modal -->
      <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in zoom-in-95 fade-in duration-300">
        <div v-if="passwordStatus === 'success'" class="p-12 text-center animate-in fade-in duration-500">
          <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <ShieldCheck class="size-12" />
          </div>
          <h2 class="text-2xl font-black text-slate-900 mb-2">Senha Atualizada!</h2>
          <p class="text-slate-500">Sua segurança é nossa prioridade. O modal fechará em instantes.</p>
        </div>
        
        <template v-else>
          <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between bg-slate-50/50">
            <h2 class="text-xl font-black text-slate-900">Atualizar Senha</h2>
            <button 
              @click="isPasswordModalOpen = false"
              class="p-2 hover:bg-white rounded-xl transition-all text-slate-400 hover:text-slate-900"
            >
              <X class="size-6" />
            </button>
          </div>
          
          <form @submit.prevent="handlePasswordChange" class="p-8 space-y-6">
            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Senha Atual</label>
              <div class="relative group">
                <Lock class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                <input 
                  required
                  :type="showPasswords ? 'text' : 'password'"
                  class="w-full pl-12 pr-12 py-4 bg-slate-50 border border-slate-100 rounded-[1.2rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium"
                  v-model="passwordData.current"
                />
                <button 
                  type="button"
                  @click="showPasswords = !showPasswords"
                  class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                  <EyeOff v-if="showPasswords" class="size-5" />
                  <Eye v-else class="size-5" />
                </button>
              </div>
            </div>

            <div class="h-px bg-slate-100 my-2"></div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Nova Senha</label>
              <div class="relative group">
                <input 
                  required
                  :type="showPasswords ? 'text' : 'password'"
                  placeholder="Mínimo 8 caracteres"
                  class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-[1.2rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium"
                  v-model="passwordData.new"
                />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Confirmar Nova Senha</label>
              <div class="relative group">
                <input 
                  required
                  :type="showPasswords ? 'text' : 'password'"
                  v-model="passwordData.confirm"
                  :class="['w-full px-5 py-4 bg-slate-50 border rounded-[1.2rem] focus:ring-4 outline-none transition-all text-sm font-medium',
                    passwordData.confirm && passwordData.new !== passwordData.confirm 
                    ? 'border-red-200 focus:ring-red-50 focus:border-red-300' 
                    : 'border-slate-100 focus:ring-blue-50 focus:border-blue-200 focus:bg-white'
                  ]"
                />
                <Check v-if="passwordData.confirm && passwordData.new === passwordData.confirm" class="absolute right-5 top-1/2 -translate-y-1/2 size-5 text-green-500" />
              </div>
              <p v-if="passwordData.confirm && passwordData.new !== passwordData.confirm" class="text-[11px] font-bold text-red-500 ml-1 animate-in fade-in slide-in-from-top-1">As senhas não coincidem.</p>
            </div>

            <div class="pt-4 flex gap-3">
              <button 
                type="button"
                @click="isPasswordModalOpen = false"
                class="flex-1 px-6 py-4 border border-slate-200 text-slate-600 rounded-[1.2rem] font-black text-sm hover:bg-slate-50 transition-all"
              >
                Cancelar
              </button>
              <button 
                type="submit"
                :disabled="isSaving || !passwordData.new || passwordData.new !== passwordData.confirm"
                class="flex-[2] px-6 py-4 bg-blue-600 text-white rounded-[1.2rem] font-black text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <div v-if="isSaving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                <template v-else>Confirmar Alteração</template>
              </button>
            </div>
          </form>
        </template>
      </div>
    </div>
  </div>
</template>
