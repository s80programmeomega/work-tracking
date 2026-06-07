<!-- Éditeur de texte riche Tiptap pour les articles du centre d'aide.
     Le HTML produit est ré-assaini côté serveur (mews/purifier, allowlist help-article). -->
<template>
  <div class="rounded-3 border border-gray-300 dark:border-gray-700">
    <!-- Barre d'outils -->
    <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b border-gray-200 p-2 dark:border-gray-700">
      <button type="button" :class="btn(editor.isActive('bold'))" @click="editor.chain().focus().toggleBold().run()" title="Gras"><b>B</b></button>
      <button type="button" :class="btn(editor.isActive('italic'))" @click="editor.chain().focus().toggleItalic().run()" title="Italique"><i>I</i></button>
      <button type="button" :class="btn(editor.isActive('strike'))" @click="editor.chain().focus().toggleStrike().run()" title="Barré"><s>S</s></button>
      <span class="mx-1 h-5 w-px bg-gray-200 dark:bg-gray-700"></span>
      <button type="button" :class="btn(editor.isActive('heading', { level: 2 }))" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" title="Titre 2">H2</button>
      <button type="button" :class="btn(editor.isActive('heading', { level: 3 }))" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" title="Titre 3">H3</button>
      <span class="mx-1 h-5 w-px bg-gray-200 dark:bg-gray-700"></span>
      <button type="button" :class="btn(editor.isActive('bulletList'))" @click="editor.chain().focus().toggleBulletList().run()" title="Liste à puces">• Liste</button>
      <button type="button" :class="btn(editor.isActive('orderedList'))" @click="editor.chain().focus().toggleOrderedList().run()" title="Liste numérotée">1. Liste</button>
      <button type="button" :class="btn(editor.isActive('blockquote'))" @click="editor.chain().focus().toggleBlockquote().run()" title="Citation">❝</button>
      <button type="button" :class="btn(editor.isActive('codeBlock'))" @click="editor.chain().focus().toggleCodeBlock().run()" title="Code">&lt;/&gt;</button>
      <span class="mx-1 h-5 w-px bg-gray-200 dark:bg-gray-700"></span>
      <button type="button" :class="btn(editor.isActive('link'))" @click="setLink" title="Lien">🔗</button>
      <button type="button" :class="btn(false)" @click="triggerImageUpload" title="Image" :disabled="uploading">🖼️</button>
      <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden" @change="onImageSelected" />
      <span v-if="uploading" class="ml-1 text-xs text-gray-400">{{ $t('help.loading') }}</span>
    </div>

    <!-- Zone d'édition -->
    <editor-content
      :editor="editor"
      class="help-editor-content prose prose-sm min-h-45 max-w-none bg-white p-4 text-gray-900 dark:prose-invert dark:bg-gray-800 dark:text-white"
    />
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import api from '@/api/axios'

const props = defineProps({
  modelValue: { type: String, default: '' },
  // ID de l'article pour rattacher les images uploadées (null avant création).
  articleId: { type: [Number, null], default: null },
})
const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)
const uploading = ref(false)

const btn = (active) => [
  'rounded px-2 py-1 text-sm transition-colors',
  active
    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
]

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Image,
    Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' } }),
  ],
  onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
})

// Synchronise le contenu externe (ex. chargement d'un article existant) sans boucle.
watch(() => props.modelValue, (value) => {
  if (editor.value && value !== editor.value.getHTML()) {
    editor.value.commands.setContent(value, false)
  }
})

const setLink = () => {
  const previous = editor.value.getAttributes('link').href
  const url = window.prompt('URL du lien', previous || 'https://')
  if (url === null) return
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

const triggerImageUpload = () => fileInput.value?.click()

const onImageSelected = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  // Sans article enregistré, on ne peut pas rattacher l'image côté serveur.
  if (!props.articleId) {
    window.alert('Enregistrez d\'abord l\'article (brouillon) avant d\'ajouter des images.')
    event.target.value = ''
    return
  }

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const { data } = await api.post(`/admin/help/articles/${props.articleId}/images`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    editor.value.chain().focus().setImage({ src: data.image.url, alt: file.name }).run()
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}

onBeforeUnmount(() => editor.value?.destroy())
</script>

<style scoped>
/* La zone éditable ProseMirror : curseur visible + bonne hauteur dans les deux thèmes. */
.help-editor-content :deep(.ProseMirror) {
  min-height: 11.25rem; /* 180px — cohérent avec min-h-45 */
  outline: none;
  caret-color: currentColor; /* le curseur suit la couleur du texte (gris foncé clair / blanc sombre) */
}

/* Placeholder lisible quand l'éditeur est vide (StarterKit n'en ajoute pas par défaut). */
.help-editor-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  color: rgb(156 163 175); /* gray-400 */
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}
</style>
