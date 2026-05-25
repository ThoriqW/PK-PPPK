<template>
    <div class="border rounded-md overflow-hidden">
        <div class="flex flex-wrap gap-1 p-2 border-b bg-slate-50 text-sm">
            <button type="button" class="btn-secondary px-2 py-1" @click="cmd.toggleBold()">B</button>
            <button type="button" class="btn-secondary px-2 py-1 italic" @click="cmd.toggleItalic()">I</button>
            <button type="button" class="btn-secondary px-2 py-1" @click="cmd.toggleHeading({ level: 1 })">H1</button>
            <button type="button" class="btn-secondary px-2 py-1" @click="cmd.toggleHeading({ level: 2 })">H2</button>
            <button type="button" class="btn-secondary px-2 py-1" @click="cmd.toggleBulletList()">•</button>
            <button type="button" class="btn-secondary px-2 py-1" @click="cmd.toggleOrderedList()">1.</button>
            <span class="mx-2 border-l h-6"></span>
            <select class="input py-1" @change="onPlaceholderChange($event)">
                <option value="">+ Sisip Placeholder</option>
                <option v-for="p in placeholders" :key="p.key" :value="p.key">{{ placeholderLabel(p) }}</option>
            </select>
        </div>
        <editor-content :editor="editor" />
    </div>
</template>

<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';

const props = defineProps({ modelValue: String, placeholders: { type: Array, default: () => [] } });
const emit = defineEmits(['update:modelValue']);

const editor = new Editor({
    content: props.modelValue || '',
    extensions: [StarterKit],
    onUpdate({ editor }) { emit('update:modelValue', editor.getHTML()); },
});
const cmd = editor.commands;

watch(() => props.modelValue, (val) => {
    if (val !== editor.getHTML()) editor.commands.setContent(val || '', false);
});
function placeholderLabel(p) {
    // Build the display label in JS so Vue's template parser does not see nested {{ }}.
    return `${p.label} (${'{{' + p.key + '}}'})`;
}

function onPlaceholderChange(event) {
    const key = event.target.value;
    if (!key) return;
    editor.commands.insertContent('{{' + key + '}}');
    event.target.value = '';
}

onBeforeUnmount(() => editor.destroy());
</script>
