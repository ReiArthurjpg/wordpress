<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { 
  User, Mail, Lock, LogIn, UserPlus, ArrowRight, ArrowLeft, 
  Globe, Camera, Calendar, CreditCard, Search, Filter,
  LayoutGrid, List, MoreVertical, Pencil, Trash2, Eye, LogOut,
  ChevronDown, Settings, Zap, X, Upload, AlertTriangle, Save, Check, ExternalLink,
  EyeOff, Plus, Edit2
} from 'lucide-vue-next'

const appLoading = useState('appLoading')
const { $toast } = useNuxtApp()

// Autenticação básica wp
const authHeader = 'Basic ' + btoa('Arthur:GOMhOPKqrNfzTPKuCPyFln67')

onMounted(() => {
  const authCookie = useCookie('auth_user')
  if (!authCookie.value) {
    navigateTo('/')
  }
})

const handleLogout = () => {
  const authCookie = useCookie('auth_user')
  authCookie.value = null
  $toast?.success('Você saiu do sistema.')
  navigateTo('/')
}

// Fetch dos usuários do WP REST API
const { data: usuarios, pending, refresh } = await useFetch(
  'http://localhost:8000/wp-json/wp/v2/usuarios'
)

watch(pending, (val) => {
  appLoading.value = val
}, { immediate: true })

// Variáveis de estado
const viewMode = ref('grid')
const showProfileMenu = ref(false)
const searchQuery = ref('')
const greeting = ref('')

const updateGreeting = () => {
  const hours = new Date().getHours()
  if (hours >= 5 && hours < 12) greeting.value = 'Bom dia'
  else if (hours >= 12 && hours < 18) greeting.value = 'Boa tarde'
  else greeting.value = 'Boa noite'
}

let greetingInterval = null
onMounted(() => {
  updateGreeting()
  greetingInterval = setInterval(updateGreeting, 60000)
})

onUnmounted(() => {
  if (greetingInterval) clearInterval(greetingInterval)
})

// Computed para filtro
const filteredUsuarios = computed(() => {
  if (!usuarios.value) return []
  return usuarios.value.filter(u => {
    const nome = u.acf?.nome?.toLowerCase() || ''
    const email = u.acf?.email?.toLowerCase() || ''
    const q = searchQuery.value.toLowerCase()
    return nome.includes(q) || email.includes(q)
  })
})

// Usuário logado
const loggedUser = computed(() => {
  const authCookie = useCookie('auth_user')
  if (!usuarios.value || !authCookie.value) return null
  return usuarios.value.find(u => u.id === Number(authCookie.value))
})

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  if (dateStr.length === 8 && !dateStr.includes('-')) {
    return `${dateStr.substring(6,8)}/${dateStr.substring(4,6)}/${dateStr.substring(0,4)}`
  }
  return dateStr.split('-').reverse().join('/')
}

// Modais
const isModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isViewModalOpen = ref(false)

const selectedUser = ref(null)
const userToDelete = ref(null)

const isSubmitting = ref(false)

// Estado para Upload de Imagem
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

// Formulários
const createForm = ref({ nome: '', email: '', cpf: '', data_de_nascimento: '', senha: '' })

// Handlers de View
const handleViewClick = (user) => {
  selectedUser.value = user
  isViewModalOpen.value = true
}

const handleEditClick = (user) => {
  selectedUser.value = user
  isViewModalOpen.value = false
  isEditModalOpen.value = true
}

const handleDeleteClick = (user) => {
  userToDelete.value = user
  isDeleteModalOpen.value = true
}

// Operações CRUD Endpoints
const handleCreateSubmit = async () => {
  if (isSubmitting.value) return
  
  if (!createForm.value.nome || !createForm.value.email || !createForm.value.cpf || !createForm.value.senha) {
    $toast?.error('Preencha os campos obrigatórios.')
    return
  }

  isSubmitting.value = true
  appLoading.value = true
  
  try {
    let imageId = null

    // 1. Upload da imagem se houver
    if (selectedFile.value) {
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      formData.append('title', `Avatar ${createForm.value.nome}`)
      
      const mediaResponse = await $fetch('http://localhost:8000/wp-json/wp/v2/media', {
        method: 'POST',
        headers: { 'Authorization': authHeader },
        body: formData
      })
      imageId = mediaResponse.id
    }

    const formattedDate = createForm.value.data_de_nascimento 
      ? createForm.value.data_de_nascimento.replace(/-/g, '') 
      : ''

    await $fetch('http://localhost:8000/wp-json/wp/v2/usuarios', {
      method: 'POST',
      headers: {
        'Authorization': authHeader,
        'Content-Type': 'application/json'
      },
      body: {
        title: createForm.value.nome,
        status: 'publish',
        acf: {
          nome: createForm.value.nome,
          email: createForm.value.email,
          cpf: createForm.value.cpf,
          data_de_nascimento: formattedDate,
          senha: createForm.value.senha,
          imagem: imageId
        }
      }
    })
    
    $toast?.success('Usuário criado com sucesso!')
    isModalOpen.value = false
    createForm.value = { nome: '', email: '', cpf: '', data_de_nascimento: '', senha: '' }
    selectedFile.value = null
    imagePreview.value = null
    await refresh()
  } catch (error) {
    console.error('Erro ao criar usuário:', error)
    $toast?.error('Erro ao criar usuário.')
  } finally {
    isSubmitting.value = false
    appLoading.value = false
  }
}

const handleEditSubmit = async () => {
  if (isSubmitting.value) return
  isSubmitting.value = true
  appLoading.value = true

  try {
    let imageId = selectedUser.value.acf?.imagem?.id || null

    // Upload de nova imagem se selecionada
    if (selectedFile.value) {
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      formData.append('title', `Avatar ${selectedUser.value.acf.nome}`)
      
      const mediaResponse = await $fetch('http://localhost:8000/wp-json/wp/v2/media', {
        method: 'POST',
        headers: { 'Authorization': authHeader },
        body: formData
      })
      imageId = mediaResponse.id
    }

    const formattedDate = selectedUser.value.acf.data_de_nascimento?.includes('-') 
      ? selectedUser.value.acf.data_de_nascimento.replace(/-/g, '') 
      : selectedUser.value.acf.data_de_nascimento

    await $fetch(`http://localhost:8000/wp-json/wp/v2/usuarios/${selectedUser.value.id}`, {
      method: 'POST',
      headers: { 'Authorization': authHeader },
      body: {
        title: selectedUser.value.acf.nome,
        acf: {
          nome: selectedUser.value.acf.nome,
          email: selectedUser.value.acf.email,
          cpf: selectedUser.value.acf.cpf,
          data_de_nascimento: formattedDate,
          imagem: imageId
        }
      }
    })

    isEditModalOpen.value = false
    selectedFile.value = null
    imagePreview.value = null
    $toast?.success('Usuário editado com sucesso!')
    await refresh()
  } catch (error) {
    console.error('Erro ao editar usuário:', error)
    $toast?.error('Erro ao editar usuário.')
  } finally {
    isSubmitting.value = false
    appLoading.value = false
  }
}

const confirmDelete = async () => {
  if (isSubmitting.value || !userToDelete.value) return
  isSubmitting.value = true
  appLoading.value = true

  try {
    await $fetch(`http://localhost:8000/wp-json/wp/v2/usuarios/${userToDelete.value.id}?force=true`, {
      method: 'DELETE',
      headers: { 'Authorization': authHeader }
    })
    
    isDeleteModalOpen.value = false
    userToDelete.value = null
    $toast?.success('Usuário excluído com sucesso!')
    await refresh()
  } catch (error) {
    console.error('Erro ao excluir:', error)
    $toast?.error('Erro ao excluir usuário.')
  } finally {
    isSubmitting.value = false
    appLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#f8fafc] font-sans text-slate-900">
    <!-- Input de arquivo invisível para upload -->
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
          <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
            <Zap class="w-6 h-6 text-white fill-white" />
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="relative">
            <button 
              @click="showProfileMenu = !showProfileMenu"
              class="flex items-center gap-3 p-1.5 pr-3 hover:bg-slate-50 rounded-2xl transition-all border border-transparent hover:border-slate-100"
            >
              <div v-if="loggedUser?.acf?.imagem" class="w-9 h-9 rounded-xl overflow-hidden shadow-sm">
                <img :src="loggedUser.acf.imagem.url" class="w-full h-full object-cover" />
              </div>
              <div v-else class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                {{ loggedUser?.acf?.nome?.charAt(0).toUpperCase() || 'U' }}
              </div>
              <div class="text-left">
                <p class="text-sm font-bold leading-none">{{ loggedUser?.acf?.nome || 'Usuário' }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ loggedUser?.acf?.email || 'email@exemplo.com' }}</p>
              </div>
              <ChevronDown :class="['w-4 h-4 text-slate-400 transition-transform', showProfileMenu ? 'rotate-180' : '']" />
            </button>

            <div v-if="showProfileMenu" class="absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-50 animate-in fade-in zoom-in-95 duration-200">
              <div class="px-4 py-3 border-b border-slate-50 mb-1">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sua Conta</p>
                <p class="text-sm font-bold text-slate-800 mt-0.5 truncate">{{ loggedUser?.acf?.nome || 'Usuário' }}</p>
                <p class="text-xs text-slate-400 truncate">{{ loggedUser?.acf?.email || '' }}</p>
              </div>
              <button @click="handleLogout" class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-3 hover:bg-red-50 text-red-600 transition-colors">
                <LogOut class="w-4 h-4" /> Sair da conta
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <!-- BOAS-VINDAS -->
      <section class="mb-10 animate-in fade-in slide-in-from-left-6 duration-700 text-center md:text-left">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
          {{ greeting }}, {{ loggedUser?.acf?.nome || 'Usuário' }}!
        </h1>
        <p class="text-slate-500 mt-2 text-lg max-w-2xl leading-relaxed mx-auto md:mx-0">
          Gerencie sua base de usuários com facilidade. Explore os detalhes e realize ações rápidas.
        </p>
      </section>

      <!-- CONTROLES -->
      <section class="mb-8 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-100">
        <div class="bg-white p-4 rounded-[24px] border border-slate-100 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
          <div class="flex flex-1 flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            <div class="relative group w-full sm:w-80">
              <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4 group-focus-within:text-blue-600 transition-colors" />
              <input 
                type="text" 
                placeholder="Buscar nome ou e-mail..."
                class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 border border-slate-100 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm"
                v-model="searchQuery"
              />
            </div>
          </div>

          <div class="flex items-center gap-3 w-full md:w-auto justify-end border-t md:border-t-0 pt-4 md:pt-0">
            <div class="hidden md:flex bg-slate-100/80 p-1 rounded-xl items-center">
              <button @click="viewMode = 'grid'" :class="['p-2 rounded-lg transition-all', viewMode === 'grid' ? 'bg-white shadow-sm text-blue-600' : 'text-slate-500 hover:text-slate-700']"><LayoutGrid class="w-4 h-4" /></button>
              <button @click="viewMode = 'list'" :class="['p-2 rounded-lg transition-all', viewMode === 'list' ? 'bg-white shadow-sm text-blue-600' : 'text-slate-500 hover:text-slate-700']"><List class="w-4 h-4" /></button>
            </div>
            <button @click="isModalOpen = true" class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all shadow-md active:scale-95 text-sm whitespace-nowrap">
              <UserPlus class="w-4 h-4" /> Novo Usuário
            </button>
          </div>
        </div>
      </section>

      <!-- LISTAGEM -->
      <section class="animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-200">
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="user in filteredUsuarios" :key="user.id" class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_50px_rgba(8,112,184,0.07)] transition-all duration-500 group relative flex flex-col items-center text-center">
            <div class="relative mb-6">
              <div class="absolute inset-0 bg-blue-500/10 rounded-3xl blur-2xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
              <img v-if="user.acf?.imagem" :src="user.acf.imagem.url" class="relative w-24 h-24 rounded-[2rem] object-cover shadow-inner transition-transform duration-500 group-hover:scale-110 ring-1 ring-black/5" />
              <div v-else :class="['relative w-24 h-24 rounded-[2rem] flex items-center justify-center text-2xl font-black shadow-inner transition-transform duration-500 group-hover:scale-110', user.id % 2 === 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-blue-50 text-blue-600']">
                {{ user.acf?.nome?.charAt(0).toUpperCase() || 'U' }}
              </div>
            </div>
            <h3 class="font-black text-xl text-slate-900 tracking-tight group-hover:text-blue-600 transition-colors duration-300 mb-6">{{ user.acf?.nome || 'Sem nome' }}</h3>
            <div class="w-full space-y-4 mb-8">
              <div class="flex flex-col items-center">
                <span class="text-[10px] uppercase font-bold text-slate-300 tracking-[0.2em] mb-1">E-mail Corporativo</span>
                <span class="text-sm font-medium text-slate-600">{{ user.acf?.email || 'N/A' }}</span>
              </div>
            </div>
            <div class="w-full flex gap-3 pt-2">
              <button @click="handleViewClick(user)" class="flex-1 py-3.5 bg-slate-50 hover:bg-blue-50 text-slate-400 hover:text-blue-600 rounded-2xl transition-all duration-300 flex items-center justify-center active:scale-95"><Eye class="w-4 h-4" /></button>
              <button @click="handleEditClick(user)" class="flex-1 py-3.5 bg-slate-50 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 rounded-2xl transition-all duration-300 flex items-center justify-center active:scale-95"><Edit2 class="w-4 h-4" /></button>
              <button @click="handleDeleteClick(user)" class="flex-1 py-3.5 bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-2xl transition-all duration-300 flex items-center justify-center active:scale-95"><Trash2 class="w-4 h-4" /></button>
            </div>
          </div>
        </div>

        <div v-else class="hidden md:block bg-white rounded-[2rem] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[700px]">
            <thead>
              <tr class="bg-slate-50/40 border-b border-slate-100">
                <th class="pl-10 pr-6 py-6 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Usuário & Contato</th>
                <th class="px-6 py-6 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Identificação</th>
                <th class="px-6 py-6 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Nascimento</th>
                <th class="pr-10 pl-6 py-6 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="user in filteredUsuarios" :key="user.id" class="hover:bg-blue-50/40 transition-all duration-300 group">
                <td class="pl-10 pr-6 py-6">
                  <div class="flex items-center gap-5">
                    <img v-if="user.acf?.imagem" :src="user.acf.imagem.url" class="w-14 h-14 rounded-2xl object-cover shadow-sm transition-all duration-500 group-hover:rounded-[1.5rem] group-hover:rotate-3 group-hover:scale-110 ring-1 ring-black/5" />
                    <div v-else :class="['w-14 h-14 rounded-2xl flex items-center justify-center font-black text-xl shadow-sm transition-all duration-500 group-hover:rounded-[1.5rem] group-hover:rotate-3 group-hover:scale-110', user.id % 2 === 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700']">
                      {{ user.acf?.nome?.charAt(0).toUpperCase() || 'U' }}
                    </div>
                    <div class="flex flex-col">
                      <p class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ user.acf?.nome || 'Sem nome' }}</p>
                      <span class="text-xs text-slate-400">{{ user.acf?.email || 'N/A' }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-6 font-bold text-slate-700 text-sm">{{ user.acf?.cpf || 'N/A' }}</td>
                <td class="px-6 py-6 font-bold text-slate-700 text-sm">{{ formatDate(user.acf?.data_de_nascimento) }}</td>
                <td class="pr-10 pl-6 py-6 text-right">
                  <div class="flex justify-end gap-2.5">
                    <button @click="handleViewClick(user)" class="p-3 bg-white border border-slate-100 text-slate-400 hover:text-blue-600 rounded-xl transition-all active:scale-90"><Eye class="w-4 h-4" /></button>
                    <button @click="handleEditClick(user)" class="p-3 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 rounded-xl transition-all active:scale-90"><Edit2 class="w-4 h-4" /></button>
                    <button @click="handleDeleteClick(user)" class="p-3 bg-white border border-slate-100 text-slate-400 hover:text-red-600 rounded-xl transition-all active:scale-90"><Trash2 class="w-4 h-4" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- MODAL DE VISUALIZAÇÃO (NOVO) -->
    <div v-if="isViewModalOpen && selectedUser" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300" @click="isViewModalOpen = false"></div>
      <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in-95 slide-in-from-bottom-8 duration-500">
        <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Detalhes do Usuário</h2>
            <p class="text-sm text-slate-500">Visualizando informações completas do registro.</p>
          </div>
          <button @click="isViewModalOpen = false" class="p-3 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 rounded-2xl transition-all">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-8 max-h-[75vh] overflow-y-auto">
          <div class="flex flex-col items-center mb-10">
            <img v-if="selectedUser.acf?.imagem" :src="selectedUser.acf.imagem.url" class="w-28 h-28 rounded-[2.5rem] object-cover border-2 border-slate-100 shadow-inner" />
            <div v-else class="w-28 h-28 rounded-[2.5rem] bg-slate-50 border-2 border-slate-100 flex items-center justify-center font-black text-4xl text-blue-600 shadow-inner">
              {{ selectedUser.acf?.nome?.charAt(0).toUpperCase() || 'U' }}
            </div>
            <h3 class="mt-4 text-2xl font-black text-slate-900">{{ selectedUser.acf?.nome || 'Sem nome' }}</h3>
            <span class="px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-xs font-black uppercase tracking-widest mt-2">Usuário Ativo</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300">E-mail Corporativo</label>
              <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                <Mail class="w-4 h-4 text-slate-400" /> {{ selectedUser.acf?.email || 'N/A' }}
              </p>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300">Documento CPF</label>
              <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                <CreditCard class="w-4 h-4 text-slate-400" /> {{ selectedUser.acf?.cpf || 'N/A' }}
              </p>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300">Data de Nascimento</label>
              <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                <Calendar class="w-4 h-4 text-slate-400" /> {{ formatDate(selectedUser.acf?.data_de_nascimento) }}
              </p>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300">ID do Registro</label>
              <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                <ExternalLink class="w-4 h-4 text-slate-400" /> #USR-{{ selectedUser.id }}
              </p>
            </div>
          </div>
        </div>

        <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-50 flex items-center justify-end gap-3">
          <button @click="isViewModalOpen = false" class="px-6 py-3.5 text-slate-500 rounded-2xl font-bold text-sm hover:bg-slate-100 transition-all active:scale-95">
            Fechar
          </button>
          <button @click="handleEditClick(selectedUser)" class="px-10 py-3.5 bg-blue-600 text-white rounded-2xl font-black text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 active:scale-95 flex items-center gap-2">
            <Edit2 class="w-4 h-4" />
            Editar Perfil
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL DE EDIÇÃO COMPLETO -->
    <div v-if="isEditModalOpen && selectedUser" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300" @click="isEditModalOpen = false"></div>
      <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in-95 slide-in-from-bottom-8 duration-500">
        <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Editar Usuário</h2>
            <p class="text-sm text-slate-500">Altere as informações necessárias do cadastro.</p>
          </div>
          <button @click="isEditModalOpen = false" class="p-3 bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-2xl transition-all">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-8 max-h-[75vh] overflow-y-auto">
          <form class="space-y-8" @submit.prevent="handleEditSubmit">
            <div class="flex flex-col items-center gap-4">
              <div class="relative group cursor-pointer" @click="triggerFileInput">
                <div class="w-24 h-24 rounded-[2.2rem] bg-indigo-50 border-2 border-indigo-100 flex items-center justify-center overflow-hidden transition-all group-hover:scale-105 relative">
                  <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                  <img v-else-if="selectedUser.acf?.imagem" :src="selectedUser.acf.imagem.url" class="w-full h-full object-cover" />
                  <span v-else class="text-3xl font-black text-indigo-600">{{ selectedUser.acf?.nome?.charAt(0).toUpperCase() || 'U' }}</span>
                  <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <Camera class="w-6 h-6 text-white" />
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Nome Completo</label>
                <div class="relative group">
                  <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="text" v-model="selectedUser.acf.nome" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">E-mail Corporativo</label>
                <div class="relative group">
                  <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="email" v-model="selectedUser.acf.email" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">CPF</label>
                <div class="relative group">
                  <CreditCard class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="text" v-model="selectedUser.acf.cpf" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Nascimento</label>
                <div class="relative group">
                  <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="date" v-model="selectedUser.acf.data_de_nascimento" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>
            </div>

            <div class="px-2 pt-2 bg-slate-50/0 flex items-center justify-end gap-3 mt-4">
              <button type="button" @click="isEditModalOpen = false" class="px-6 py-3.5 border-2 border-slate-200 text-slate-500 rounded-2xl font-bold text-sm hover:bg-slate-100 transition-all active:scale-95">
                Cancelar
              </button>
              <button type="submit" class="px-10 py-3.5 bg-[#22c55e] text-white rounded-2xl font-black text-sm hover:bg-[#16a34a] transition-all shadow-lg shadow-green-100 active:scale-95 flex items-center gap-2">
                <Check class="w-4 h-4" />
                Atualizar Dados
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>

    <!-- MODAL DE CRIAÇÃO (COM CAMPOS COMPLETOS BASEADOS NA EDIÇÃO) -->
    <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isModalOpen = false"></div>
      <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in-95 slide-in-from-bottom-8 duration-300">
        <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Novo Usuário</h2>
            <p class="text-sm text-slate-500">Interface de criação de novo registro.</p>
          </div>
          <button @click="isModalOpen = false" class="p-3 bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-2xl transition-all">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-8 max-h-[75vh] overflow-y-auto custom-scrollbar">
          <form class="space-y-8" @submit.prevent="handleCreateSubmit">
            
            <div class="flex flex-col items-center gap-4 mb-4">
              <div class="relative group cursor-pointer" @click="triggerFileInput">
                <div class="w-24 h-24 rounded-[2.2rem] bg-indigo-50 border-2 border-indigo-100 flex items-center justify-center overflow-hidden transition-all group-hover:scale-105 relative">
                  <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                  <div v-else class="flex flex-col items-center justify-center text-indigo-300">
                    <Camera class="w-8 h-8 mb-1" />
                    <span class="text-[9px] font-black uppercase">Foto</span>
                  </div>
                  <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <Camera class="w-6 h-6 text-white" />
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Nome Completo</label>
                <div class="relative group">
                  <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="text" v-model="createForm.nome" required placeholder="Digite o nome" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">E-mail Corporativo</label>
                <div class="relative group">
                  <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="email" v-model="createForm.email" required placeholder="email@empresa.com" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">CPF</label>
                <div class="relative group">
                  <CreditCard class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="text" v-model="createForm.cpf" required placeholder="000.000.000-00" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Nascimento</label>
                <div class="relative group">
                  <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="date" v-model="createForm.data_de_nascimento" required class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>

              <!-- Criei o campo de senha que é necessário pro banco WP -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Senha de Acesso</label>
                <div class="relative group">
                  <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                  <input type="password" v-model="createForm.senha" required placeholder="••••••••" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-200 focus:bg-white outline-none transition-all text-sm font-medium" />
                </div>
              </div>

            </div>
            
            <div class="px-2 pt-2 flex items-center justify-end gap-3 mt-4">
              <button type="button" @click="isModalOpen = false" class="px-6 py-3.5 border-2 border-slate-200 text-slate-500 rounded-2xl font-bold text-sm hover:bg-slate-100 transition-all active:scale-95">
                Cancelar
              </button>
              <button type="submit" class="px-10 py-3.5 bg-blue-600 text-white rounded-2xl font-black text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 active:scale-95 flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Criar Usuário
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL DE EXCLUSÃO -->
    <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" @click="isDeleteModalOpen = false"></div>
      <div class="relative w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl p-8 text-center animate-in zoom-in-95 duration-300">
        <div class="w-20 h-20 bg-red-50 rounded-[2rem] flex items-center justify-center mb-6 mx-auto">
          <AlertTriangle class="w-10 h-10 text-red-600" />
        </div>
        <h2 class="text-2xl font-black mb-2">Confirmar Exclusão</h2>
        <p class="text-slate-500 mb-8">Excluir permanentemente o usuário <span class="font-bold text-slate-900">{{ userToDelete?.acf?.nome || 'esse registro' }}</span>?</p>
        <div class="grid grid-cols-1 gap-3">
          <button @click="confirmDelete" class="w-full py-4 bg-red-600 hover:bg-red-700 transition-colors text-white rounded-2xl font-black shadow-xl shadow-red-200 active:scale-95">Sim, excluir agora</button>
          <button @click="isDeleteModalOpen = false" class="w-full py-4 border-2 border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors rounded-2xl font-bold active:scale-95">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</template>